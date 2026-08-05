use leptos::prelude::*;
use leptos_router::hooks::use_location;

#[component]
pub fn AppShell(children: Children) -> impl IntoView {
    let mobile_open = RwSignal::new(false);

    view! {
        <div class="min-h-screen bg-background md:grid md:grid-cols-[16rem_minmax(0,1fr)]">
            <aside class="sticky top-0 hidden h-screen border-r border-sidebar-border bg-sidebar text-sidebar-foreground md:flex md:flex-col">
                <SidenavContent mobile_open />
            </aside>

            <div
                class=move || {
                    if mobile_open.get() {
                        "fixed inset-0 z-50 visible md:hidden"
                    } else {
                        "pointer-events-none fixed inset-0 z-50 invisible md:hidden"
                    }
                }
                aria-hidden=move || (!mobile_open.get()).to_string()
            >
                <button
                    type="button"
                    class=move || {
                        if mobile_open.get() {
                            "absolute inset-0 bg-black/45 opacity-100 transition-opacity"
                        } else {
                            "absolute inset-0 bg-black/45 opacity-0 transition-opacity"
                        }
                    }
                    aria-label="Close navigation"
                    on:click=move |_| mobile_open.set(false)
                ></button>

                <aside
                    class=move || {
                        if mobile_open.get() {
                            "relative flex h-full w-[18rem] max-w-[86vw] translate-x-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-xl transition-transform duration-200"
                        } else {
                            "relative flex h-full w-[18rem] max-w-[86vw] -translate-x-full flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-xl transition-transform duration-200"
                        }
                    }
                >
                    <SidenavContent mobile_open />
                </aside>
            </div>

            <div class="min-w-0">
                <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b bg-background/95 px-4 backdrop-blur md:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-foreground hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:hidden"
                            aria-label="Open navigation"
                            aria-expanded=move || mobile_open.get().to_string()
                            on:click=move |_| mobile_open.set(true)
                        >
                            <PanelLeftIcon />
                        </button>
                        <span class="truncate text-sm font-medium">"Internal operations platform"</span>
                    </div>
                    <a
                        class="rounded-md px-2 py-1 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        href="/login"
                    >
                        "Account"
                    </a>
                </header>
                <main class="p-4 md:p-6">{children()}</main>
            </div>
        </div>
    }
}

#[component]
fn SidenavContent(mobile_open: RwSignal<bool>) -> impl IntoView {
    view! {
        <div class="flex h-full min-h-0 flex-col">
            <div class="flex h-16 items-center gap-3 border-b border-sidebar-border px-4">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-sidebar-primary text-sm font-semibold text-sidebar-primary-foreground">
                    "F"
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">"FluidWeb"</p>
                    <p class="truncate text-xs text-muted-foreground">"Company workspace"</p>
                </div>
                <button
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground md:hidden"
                    aria-label="Close navigation"
                    on:click=move |_| mobile_open.set(false)
                >
                    <CloseIcon />
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-3">
                <SidenavGroup label="Workspace">
                    <SidenavLink href="/" label="Dashboard" exact=true mobile_open>
                        <DashboardIcon />
                    </SidenavLink>
                </SidenavGroup>

                <SidenavGroup label="Administration" class="mt-4">
                    <DisabledSidenavItem label="Users">
                        <UsersIcon />
                    </DisabledSidenavItem>
                    <DisabledSidenavItem label="Groups">
                        <GroupsIcon />
                    </DisabledSidenavItem>
                    <DisabledSidenavItem label="Extensions">
                        <ExtensionsIcon />
                    </DisabledSidenavItem>
                </SidenavGroup>

                <SidenavGroup label="System" class="mt-4">
                    <DisabledSidenavItem label="Settings">
                        <SettingsIcon />
                    </DisabledSidenavItem>
                </SidenavGroup>
            </div>

            <div class="border-t border-sidebar-border p-3">
                <a
                    href="/login"
                    class="flex items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    on:click=move |_| mobile_open.set(false)
                >
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-md border border-sidebar-border bg-background text-xs font-semibold">
                        "CP"
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">"Administrator"</p>
                        <p class="truncate text-xs text-muted-foreground">"Account settings"</p>
                    </div>
                    <ChevronRightIcon />
                </a>
            </div>
        </div>
    }
}

#[component]
fn SidenavGroup(
    label: &'static str,
    #[prop(optional, into)] class: String,
    children: Children,
) -> impl IntoView {
    view! {
        <details open class=class>
            <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-medium text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground [&::-webkit-details-marker]:hidden">
                <span>{label}</span>
                <ChevronDownIcon />
            </summary>
            <nav class="mt-1 grid gap-1">{children()}</nav>
        </details>
    }
}

#[component]
fn SidenavLink(
    href: &'static str,
    label: &'static str,
    exact: bool,
    mobile_open: RwSignal<bool>,
    children: Children,
) -> impl IntoView {
    let location = use_location();

    view! {
        <a
            href=href
            class=move || {
                let path = location.pathname.get();
                let active = if exact { path == href } else { path.starts_with(href) };

                if active {
                    "flex items-center gap-3 rounded-md bg-sidebar-accent px-2 py-2 text-sm font-medium text-sidebar-accent-foreground"
                } else {
                    "flex items-center gap-3 rounded-md px-2 py-2 text-sm text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                }
            }
            aria-current=move || {
                let path = location.pathname.get();
                let active = if exact { path == href } else { path.starts_with(href) };
                active.then_some("page")
            }
            on:click=move |_| mobile_open.set(false)
        >
            {children()}
            <span>{label}</span>
        </a>
    }
}

#[component]
fn DisabledSidenavItem(label: &'static str, children: Children) -> impl IntoView {
    view! {
        <button
            type="button"
            disabled
            class="flex cursor-not-allowed items-center gap-3 rounded-md px-2 py-2 text-left text-sm text-muted-foreground opacity-65"
            title=format!("{label} is not available yet")
        >
            {children()}
            <span>{label}</span>
        </button>
    }
}

#[component]
fn PanelLeftIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="3" rx="2" />
            <path d="M9 3v18" />
        </svg>
    }
}

#[component]
fn CloseIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
        </svg>
    }
}

#[component]
fn ChevronDownIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6" />
        </svg>
    }
}

#[component]
fn ChevronRightIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6" />
        </svg>
    }
}

#[component]
fn DashboardIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="7" height="9" x="3" y="3" rx="1" />
            <rect width="7" height="5" x="14" y="3" rx="1" />
            <rect width="7" height="9" x="14" y="12" rx="1" />
            <rect width="7" height="5" x="3" y="16" rx="1" />
        </svg>
    }
}

#[component]
fn UsersIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
    }
}

#[component]
fn GroupsIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 7h10" />
            <path d="M7 12h10" />
            <path d="M7 17h10" />
            <circle cx="4" cy="7" r="1" />
            <circle cx="4" cy="12" r="1" />
            <circle cx="4" cy="17" r="1" />
        </svg>
    }
}

#[component]
fn ExtensionsIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v6" />
            <path d="m15.5 5-3.5 3.5L8.5 5" />
            <rect width="16" height="12" x="4" y="9" rx="2" />
            <path d="M8 13h.01" />
            <path d="M12 13h.01" />
            <path d="M16 13h.01" />
        </svg>
    }
}

#[component]
fn SettingsIcon() -> impl IntoView {
    view! {
        <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2Z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
    }
}
