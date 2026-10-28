<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import FileText from '@lucide/svelte/icons/file-text';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import Package from '@lucide/svelte/icons/package';
    import Percent from '@lucide/svelte/icons/percent';
    import ReceiptText from '@lucide/svelte/icons/receipt-text';
    import Ruler from '@lucide/svelte/icons/ruler';
    import Settings from '@lucide/svelte/icons/settings';
    import Tags from '@lucide/svelte/icons/tags';
    import Users from '@lucide/svelte/icons/users';
    import Wallet from '@lucide/svelte/icons/wallet';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { index as categories } from '@/routes/categories';
    import { index as customers } from '@/routes/customers';
    import { index as documents } from '@/routes/documents';
    import { index as expenses } from '@/routes/expenses';
    import { index as invoices } from '@/routes/invoices';
    import { index as products } from '@/routes/products';
    import { edit as editProfile } from '@/routes/profile';
    import { index as taxRates } from '@/routes/tax-rates';
    import { index as units } from '@/routes/units';
    import type { NavGroup, NavItem } from '@/types';

    let { children }: { children?: Snippet } = $props();

    const navGroups: NavGroup[] = [
        {
            label: 'Overview',
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutDashboard,
                },
            ],
        },
        {
            label: 'Sales',
            items: [
                {
                    title: 'Invoices',
                    href: invoices(),
                    icon: ReceiptText,
                },
                {
                    title: 'Products',
                    href: products(),
                    icon: Package,
                },
                {
                    title: 'Customers',
                    href: customers(),
                    icon: Users,
                },
            ],
        },
        {
            label: 'Catalog',
            items: [
                {
                    title: 'Categories',
                    href: categories(),
                    icon: Tags,
                },
                {
                    title: 'Units',
                    href: units(),
                    icon: Ruler,
                },
                {
                    title: 'Tax rates',
                    href: taxRates(),
                    icon: Percent,
                },
            ],
        },
        {
            label: 'Operations',
            items: [
                {
                    title: 'Expenses',
                    href: expenses(),
                    icon: Wallet,
                },
                {
                    title: 'Documents',
                    href: documents(),
                    icon: FileText,
                },
            ],
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Settings',
            href: editProfile(),
            icon: Settings,
        },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        {#each navGroups as group (group.label)}
            <NavMain label={group.label} items={group.items} />
        {/each}
    </SidebarContent>

    <SidebarFooter>
        <NavFooter items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
