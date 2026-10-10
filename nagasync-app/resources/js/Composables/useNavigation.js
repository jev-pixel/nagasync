import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * One place to add or move links. Visibility here is cosmetic;
 * real access control is the `role` middleware on each route.
 * `match` lists the route patterns that mark an item as active.
 */
export function useNavigation() {
    const page = usePage();
    const role = computed(() => page.props.auth.user.role);

    const isStaff = computed(() => ['barangay_staff', 'admin'].includes(role.value));
    const isResponder = computed(() => ['mdrrmo', 'admin'].includes(role.value));
    const isTreasury = computed(() => ['treasury', 'admin'].includes(role.value));
    const isAdmin = computed(() => role.value === 'admin');

    const groups = computed(() => {
        const result = [
            {
                label: 'Services',
                items: [
                    { label: 'My Documents', route: 'documents.index', icon: 'document', match: ['documents.*'], text: 'Request and track' },
                    { label: 'Payments', route: 'payments.index', icon: 'card', match: ['payments.*'], text: 'Pay municipal fees' },
                    { label: 'Emergency', route: 'emergency.index', icon: 'alert', match: ['emergency.*'], text: 'Send an alert', danger: true },
                    { label: 'Market', route: 'market.index', icon: 'bag', match: ['market.*'], text: "Today's prices" },
                    { label: 'Polls', route: 'polls.index', icon: 'poll', match: ['polls.*'], text: 'Vote on local issues' },
                ],
            },
        ];

        const ops = [];
        if (isStaff.value) {
            ops.push(
                { label: 'Document Queue', route: 'staff.documents.index', icon: 'inbox', match: ['staff.documents.*'], text: 'Review requests' },
                { label: 'Price Entry', route: 'staff.market.index', icon: 'edit', match: ['staff.market.*'], text: 'Record market prices' },
                { label: 'Residents', route: 'staff.residents.index', icon: 'users', match: ['staff.residents.*'], text: 'Verify residents' },
            );
        }
        if (isResponder.value) {
            ops.push({ label: 'Dispatch', route: 'dispatch.index', icon: 'bell', match: ['dispatch.*'], text: 'Respond to alerts' });
        }
        if (isTreasury.value) {
            ops.push({ label: 'Treasury', route: 'treasury.payments', icon: 'chart', match: ['treasury.*'], text: 'Payment ledger' });
        }
        if (ops.length) result.push({ label: 'Operations', items: ops });

        if (isAdmin.value) {
            result.push({
                label: 'Admin',
                items: [
                    { label: 'Command Center', route: 'admin.command-center', icon: 'chart', match: ['admin.command-center'], text: 'Live overview' },
                    { label: 'Manage Polls', route: 'admin.polls.index', icon: 'poll', match: ['admin.polls.*'], text: 'Create and publish' },
                ],
            });
        }

        return result;
    });

    return { role, groups };
}

export const roleLabels = {
    citizen: 'Resident',
    barangay_staff: 'Barangay staff',
    treasury: 'Treasury',
    mdrrmo: 'Responder',
    admin: 'Administrator',
};

export const isActive = (match) => match.some((pattern) => route().current(pattern));