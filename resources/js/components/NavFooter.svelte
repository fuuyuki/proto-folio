<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import {
        SidebarGroup,
        SidebarGroupContent,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import type { NavItem } from '@/types';

    let {
        items = [],
        class: className = '',
    }: {
        items: NavItem[];
        class?: string;
    } = $props();

    const url = currentUrlState();
</script>

<SidebarGroup class={`group-data-[collapsible=icon]:p-0 ${className}`}>
    <SidebarGroupContent>
        <SidebarMenu>
            {#each items as item (toUrl(item.href))}
                <SidebarMenuItem>
                    <SidebarMenuButton
                        class="text-rail-ink-soft hover:text-rail-ink"
                        asChild
                        isActive={url.isCurrentOrParentUrl(
                            item.href,
                            url.currentUrl,
                        )}
                        tooltip={item.title}
                    >
                        {#snippet children(props)}
                            <Link
                                {...props}
                                href={toUrl(item.href)}
                                class={props.class}
                            >
                                {#if item.icon}
                                    <item.icon class="size-4 shrink-0" />
                                {/if}
                                <span>{item.title}</span>
                            </Link>
                        {/snippet}
                    </SidebarMenuButton>
                </SidebarMenuItem>
            {/each}
        </SidebarMenu>
    </SidebarGroupContent>
</SidebarGroup>
