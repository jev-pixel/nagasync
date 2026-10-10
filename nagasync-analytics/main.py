import hmac
import os
from datetime import date

import pandas as pd
from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

app = FastAPI(title="NagaSync Market Watch Analytics Engine")


def require_key(x_internal_key: str | None = Header(default=None)) -> None:
    expected = os.environ.get("ANALYTICS_KEY")
    if not expected:
        raise HTTPException(status_code=503, detail="Service key not configured")
    if not x_internal_key or not hmac.compare_digest(x_internal_key, expected):
        raise HTTPException(status_code=401, detail="Unauthorized")


class PricePoint(BaseModel):
    commodity: str
    unit: str
    recorded_on: date
    price: float = Field(ge=0)          # pesos
    srp: float | None = Field(default=None, ge=0)


class AnalyzeRequest(BaseModel):
    points: list[PricePoint] = Field(max_length=5000)


class CommodityTrend(BaseModel):
    commodity: str
    unit: str
    latest_price: float
    latest_date: date
    avg_7d: float | None
    prev_avg_7d: float | None
    change_pct: float | None
    status: str   # stable | rising | falling | alert_high | over_srp
    sparkline: list[float]


class TrendsResponse(BaseModel):
    status: str = "success"
    data: list[CommodityTrend]


def classify(latest: float, srp, change: float | None, z: float | None) -> str:
    if pd.notna(srp) and latest > float(srp):
        return "over_srp"
    if z is not None and z >= 2:
        return "alert_high"
    if change is not None and change >= 5:
        return "rising"
    if change is not None and change <= -5:
        return "falling"
    return "stable"


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/api/analytics/price-trends", response_model=TrendsResponse, dependencies=[Depends(require_key)])
def price_trends(req: AnalyzeRequest) -> TrendsResponse:
    if not req.points:
        return TrendsResponse(data=[])

    df = pd.DataFrame([p.model_dump() for p in req.points])
    df["recorded_on"] = pd.to_datetime(df["recorded_on"])

    results: list[CommodityTrend] = []
    for (commodity, unit), g in df.groupby(["commodity", "unit"]):
        g = g.sort_values("recorded_on")
        latest = g.iloc[-1]
        day = latest["recorded_on"]
        price = float(latest["price"])

        last7 = g[g["recorded_on"] > day - pd.Timedelta(days=7)]["price"]
        prev7 = g[(g["recorded_on"] <= day - pd.Timedelta(days=7))
                  & (g["recorded_on"] > day - pd.Timedelta(days=14))]["price"]

        avg7 = float(last7.mean()) if len(last7) else None
        prev = float(prev7.mean()) if len(prev7) else None
        change = round((avg7 - prev) / prev * 100, 1) if avg7 is not None and prev else None

        history = g.iloc[:-1].tail(14)["price"]
        z = None
        if len(history) >= 5 and history.std() > 0:
            z = float((price - history.mean()) / history.std())

        results.append(CommodityTrend(
            commodity=commodity,
            unit=unit,
            latest_price=round(price, 2),
            latest_date=day.date(),
            avg_7d=round(avg7, 2) if avg7 is not None else None,
            prev_avg_7d=round(prev, 2) if prev is not None else None,
            change_pct=change,
            status=classify(price, latest["srp"], change, z),
            sparkline=[round(float(v), 2) for v in g.tail(14)["price"]],
        ))

    return TrendsResponse(data=sorted(results, key=lambda r: r.commodity))