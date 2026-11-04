import {
    BadgeCheck, ClipboardList,
    Compass,
    FileText, Gauge, Landmark, ListChecks, ShoppingBasket,
    Siren,
    Store,
    Tag, UserCheck, Vote, Wallet
} from 'lucide-vue-next';

const map = {
    file: FileText, check: BadgeCheck, wallet: Wallet, siren: Siren, tag: Tag, users: UserCheck,
    vote: Vote, gauge: Gauge, list: ListChecks, store: ShoppingBasket, landmark: Landmark, clipboard: ClipboardList,
    compass: Compass, shop: Store,
};

export const iconFor = (key) => map[key] ?? FileText;