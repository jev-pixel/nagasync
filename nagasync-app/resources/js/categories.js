import { BedDouble, Coffee, Compass, HeartPulse, Landmark, ShoppingBag, Store, UtensilsCrossed, Wrench } from 'lucide-vue-next';

export const CATEGORY = {
    cafe: { label: 'Cafés', icon: Coffee, tone: 'from-orange-300 to-orange-500' },
    restaurant: { label: 'Restaurants', icon: UtensilsCrossed, tone: 'from-rose-300 to-ns-coral' },
    shop: { label: 'Shops', icon: ShoppingBag, tone: 'from-violet-300 to-ns-violet' },
    services: { label: 'Services', icon: Wrench, tone: 'from-teal-200 to-ns-teal' },
    accommodation: { label: 'Stay', icon: BedDouble, tone: 'from-sky-300 to-blue-500' },
    attraction: { label: 'Attractions', icon: Landmark, tone: 'from-amber-300 to-ns-orange' },
    tours: { label: 'Tours', icon: Compass, tone: 'from-emerald-300 to-emerald-500' },
    health: { label: 'Health', icon: HeartPulse, tone: 'from-pink-300 to-pink-500' },
    other: { label: 'Other', icon: Store, tone: 'from-slate-300 to-slate-500' },
};

export const TAG_LABEL = {
    wifi: 'Wi-Fi', 'air-conditioned': 'Air-conditioned', 'family-friendly': 'Family-friendly',
    'pet-friendly': 'Pet-friendly', parking: 'Parking', 'accepts-gcash': 'Accepts GCash',
    delivery: 'Delivery', 'local-products': 'Local products', 'pwd-accessible': 'PWD-accessible',
};

export const pesos = (n) => '₱'.repeat(n || 0);

export const STATUS_STYLE = {
    pending: 'bg-amber-100 text-amber-800', approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800', suspended: 'bg-slate-200 text-slate-700',
};