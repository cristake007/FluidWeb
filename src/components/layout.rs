use leptos::prelude::*;

#[component]
pub fn AppShell(children: Children) -> impl IntoView {
    view! {
        <div class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
            <aside class="border-r bg-[#111c33] px-4 py-5 text-white">
                <div class="mb-8 text-lg font-semibold tracking-tight">"FluidWeb"</div>
                <nav class="grid gap-1 text-sm">
                    <a class="rounded-md bg-[#d41131] px-3 py-2 font-medium" href="/">"Dashboard"</a>
                    <a class="rounded-md px-3 py-2 text-slate-300 hover:bg-white/10" href="#">"Users"</a>
                    <a class="rounded-md px-3 py-2 text-slate-300 hover:bg-white/10" href="#">"Settings"</a>
                </nav>
            </aside>
            <div>
                <header class="flex h-14 items-center justify-between border-b bg-white px-6">
                    <span class="text-sm font-medium">"Internal operations platform"</span>
                    <a class="text-sm text-muted-foreground hover:text-foreground" href="/login">"Account"</a>
                </header>
                <main class="p-6">{children()}</main>
            </div>
        </div>
    }
}
