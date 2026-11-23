import { iconFor } from '@/icons';
import { usePage } from '@inertiajs/vue3';
import { LayoutGrid, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';

const STAFF = ['barangay_staff', 'city_staff', 'admin'];

// Visibility here is cosmetic; the `role` and `resident.verified` middleware on each route
// are the real access control. Keep these lists in sync with routes/web.php.
//
//   CITIZEN         Request Documents, Market Prices, Community Polls, Explore Market
//   BARANGAY STAFF  Request Documents, Payments, Emergency, Document Queues
//   ADMIN           everything (plus the remaining admin / city / treasury / dispatch pages)
const ALL = [
    { key: 'dashboard', label: 'Home', route: 'dashboard', match: ['dashboard'], icon: 'gauge', roles: '*' },
    { key: 'guide', label: 'Guide', route: 'guide.index', match: ['guide.*'], icon: 'clipboard', roles: '*' },
    { key: 'news', label: 'News', route: 'news.index', match: ['news.*'], icon: 'newspaper', roles: '*' },
    { key: 'documents', label: 'Documents', route: 'documents.index', match: ['documents.*'], icon: 'file', roles: ['citizen', 'barangay_staff', 'admin'], needsVerified: true },
    { key: 'payments', label: 'Payments', route: 'payments.index', match: ['payments.*'], icon: 'wallet', roles: ['barangay_staff', 'admin'], needsVerified: true },
    { key: 'emergency', label: 'Emergency', route: 'emergency.index', match: ['emergency.*'], icon: 'siren', roles: ['barangay_staff', 'admin'] },
    { key: 'market', label: 'Market prices', route: 'market.index', match: ['market.*'], icon: 'store', roles: ['citizen', 'admin'] },
    { key: 'polls', label: 'Polls', route: 'polls.index', match: ['polls.*'], icon: 'vote', roles: ['citizen', 'admin'], needsVerified: true },
    { key: 'queue', label: 'Document queue', route: 'staff.documents.index', match: ['staff.documents.*'], icon: 'list', roles: STAFF },
    { key: 'counter', label: 'Counter', route: 'staff.index', match: ['staff.index', 'staff.release'], icon: 'landmark', roles: ['barangay_staff', 'city_staff', 'admin'] },
    { key: 'residents', label: 'Residents', route: 'staff.residents.index', match: ['staff.residents.*'], icon: 'users', roles: ['barangay_staff', 'admin'] },
    { key: 'prices', label: 'Price entry', route: 'staff.market.index', match: ['staff.market.*'], icon: 'tag', roles: ['city_staff', 'admin'] },
    { key: 'dispatch', label: 'Dispatch', route: 'dispatch.index', match: ['dispatch.*'], icon: 'siren', roles: ['mdrrmo', 'admin'] },
    { key: 'treasury', label: 'Treasury', route: 'treasury.payments', match: ['treasury.*'], icon: 'landmark', roles: ['treasury', 'admin'] },
    { key: 'command', label: 'Command center', route: 'admin.command-center', match: ['admin.command-center'], icon: 'gauge', roles: ['admin'] },
    { key: 'manage-polls', label: 'Manage polls', route: 'admin.polls.index', match: ['admin.polls.*'], icon: 'clipboard', roles: ['admin'] },
    { key: 'explore', label: 'Explore', route: 'explore.index', match: ['explore.*'], icon: 'compass', roles: ['citizen', 'barangay_staff', 'admin'] },
    { key: 'mybiz', label: 'My businesses', route: 'businesses.index', match: ['businesses.*'], icon: 'shop', roles: ['admin'], needsVerified: true },
    { key: 'bizreview', label: 'Business reviews', route: 'staff.businesses.index', match: ['staff.businesses.*'], icon: 'clipboard', roles: ['city_staff', 'admin'] },
];

// The three links pinned to the mobile bottom bar (the rest go under "More").
// Only keys the role can actually see are shown; missing ones are skipped.
const PINS = {
    citizen: ['dashboard', 'documents', 'market'],
    barangay_staff: ['dashboard', 'queue', 'documents'],
    city_staff: ['dashboard', 'queue', 'counter'],
    mdrrmo: ['dashboard', 'dispatch'],
    treasury: ['dashboard', 'treasury'],
    admin: ['dashboard', 'command', 'queue'],
};
// Unverified residents: the Guide is their home; Market prices and Explore are open to them
const UNVERIFIED_PINS = ['guide', 'market', 'explore'];

// The mobile bottom bar's "Activity" tab: each role's main working page
const ACTIVITY = {
    citizen: 'documents',
    barangay_staff: 'queue',
    city_staff: 'counter',
    treasury: 'treasury',
    mdrrmo: 'dispatch',
    admin: 'command',
};

export function useNavigation() {
    const page = usePage();
    const role = computed(() => page.props.auth.user.role);
    // true for staff roles and verified citizens (shared by HandleInertiaRequests)
    const verified = computed(() => page.props.auth.resident_verified !== false);

    const items = computed(() => ALL
        .filter((i) => i.roles === '*' || i.roles.includes(role.value))
        // Unverified citizens: hide features that need verification, and the dashboard (the Guide replaces it)
        .filter((i) => verified.value || (!i.needsVerified && i.key !== 'dashboard'))
        .map((i) => ({ ...i, icon: iconFor(i.icon) })));

    const pinned = computed(() => (verified.value ? (PINS[role.value] ?? PINS.citizen) : UNVERIFIED_PINS)
        .map((k) => items.value.find((i) => i.key === k)).filter(Boolean));

    const more = computed(() => items.value.filter((i) => !pinned.value.includes(i)));
    const isActive = (item) => item.match.some((p) => route().current(p));

    // Mobile bottom bar: Home, Services | (centre button) | Activity, Profile
    const tabs = computed(() => {
        const find = (k) => items.value.find((i) => i.key === k);
        const home = find(verified.value ? 'dashboard' : 'guide');
        const activity = find(ACTIVITY[role.value]);
        return {
            left: [
                home && { ...home, label: 'Home' },
                { key: 'services', label: 'Services', route: 'services.index', match: ['services.*'], icon: LayoutGrid },
            ].filter(Boolean),
            right: [
                activity && { ...activity, key: 'activity', label: 'Activity' },
                { key: 'profile', label: 'Profile', route: 'profile.edit', match: ['profile.*'], icon: UserRound },
            ].filter(Boolean),
        };
    });

    return { items, pinned, more, tabs, isActive, verified };
}
