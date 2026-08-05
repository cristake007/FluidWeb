mod dashboard;
mod login;

pub use dashboard::DashboardPage;
pub use login::LoginPage;

use leptos::prelude::*;

#[component]
pub fn NotFoundPage() -> impl IntoView {
    view! {
        <main class="grid min-h-screen place-items-center p-6">
            <div class="text-center">
                <h1 class="text-3xl font-semibold">"404"</h1>
                <p class="mt-2 text-muted-foreground">"The requested page does not exist."</p>
            </div>
        </main>
    }
}
