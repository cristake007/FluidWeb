use leptos::prelude::*;
use crate::components::layout::AppShell;

#[component]
pub fn DashboardPage() -> impl IntoView {
    view! {
        <AppShell>
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">"Dashboard"</h1>
                <p class="mt-1 text-sm text-muted-foreground">"Rust full-stack foundation is running."</p>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <Metric title="Users" value="—" />
                <Metric title="Active sessions" value="—" />
                <Metric title="Installed modules" value="0" />
            </div>
            <section class="mt-6 rounded-md border bg-white p-5 shadow-sm">
                <h2 class="font-semibold">"Platform status"</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    "Leptos SSR, hydration, GraphQL, PostgreSQL migrations and session authentication are wired into one Rust application."
                </p>
            </section>
        </AppShell>
    }
}

#[component]
fn Metric(title: &'static str, value: &'static str) -> impl IntoView {
    view! {
        <article class="rounded-md border bg-white p-5 shadow-sm">
            <p class="text-sm text-muted-foreground">{title}</p>
            <p class="mt-2 text-2xl font-semibold">{value}</p>
        </article>
    }
}
