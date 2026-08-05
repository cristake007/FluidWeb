use crate::components::ui::Button;
use leptos::prelude::*;

#[component]
pub fn LoginPage() -> impl IntoView {
    let email = NodeRef::<leptos::html::Input>::new();
    let password = NodeRef::<leptos::html::Input>::new();
    let message = RwSignal::new(String::new());
    let pending = RwSignal::new(false);

    let submit = move |_| {
        #[cfg(feature = "hydrate")]
        {
            let Some(email) = email.get() else {
                return;
            };
            let Some(password) = password.get() else {
                return;
            };
            let email = email.value();
            let password = password.value();

            pending.set(true);
            message.set(String::new());
            wasm_bindgen_futures::spawn_local(async move {
                let body = serde_json::json!({
                    "query": "mutation Login($email: String!, $password: String!) { login(email: $email, password: $password) { user { id email firstName lastName roles active } } }",
                    "variables": { "email": email, "password": password }
                });

                let request = gloo_net::http::Request::post("/graphql")
                    .header("Content-Type", "application/json")
                    .json(&body)
                    .map_err(|error| error.to_string());

                match request {
                    Ok(request) => match request.send().await {
                        Ok(response) => {
                            let value: serde_json::Value =
                                response.json().await.unwrap_or_default();
                            if value.get("errors").is_some() {
                                message.set("Invalid credentials or inactive account.".into());
                            } else if let Some(window) = web_sys::window() {
                                let _ = window.location().set_href("/");
                            }
                        }
                        Err(error) => message.set(error.to_string()),
                    },
                    Err(error) => message.set(error),
                }
                pending.set(false);
            });
        }
    };

    view! {
        <main class="grid min-h-screen place-items-center bg-[#eef1f6] p-6">
            <section class="w-full max-w-sm rounded-md border bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-primary">"FluidWeb"</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight">"Sign in"</h1>
                    <p class="mt-1 text-sm text-muted-foreground">"Use your company account."</p>
                </div>
                <div class="grid gap-4">
                    <label class="grid gap-1.5 text-sm font-medium">
                        "Email"
                        <input node_ref=email type="email" autocomplete="username" class="h-9 rounded-md border bg-white px-3 outline-none focus:ring-2 focus:ring-ring" />
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">
                        "Password"
                        <input node_ref=password type="password" autocomplete="current-password" class="h-9 rounded-md border bg-white px-3 outline-none focus:ring-2 focus:ring-ring" />
                    </label>
                    <Show when=move || !message.get().is_empty()>
                        <p class="text-sm text-[#d41131]">{move || message.get()}</p>
                    </Show>
                    <Button attr:r#type="button" on:click=submit attr:disabled=move || pending.get()>
                        {move || if pending.get() { "Signing in…" } else { "Sign in" }}
                    </Button>
                </div>
            </section>
        </main>
    }
}
