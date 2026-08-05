use leptos::prelude::*;
use leptos_meta::{Stylesheet, Title, provide_meta_context};
use leptos_router::{
    components::{Route, Router, Routes},
    path,
};

use crate::pages::{DashboardPage, LoginPage, NotFoundPage};

#[component]
pub fn App() -> impl IntoView {
    provide_meta_context();

    view! {
        <Stylesheet id="fluidweb" href="/pkg/fluidweb.css" />
        <Title text="FluidWeb" />
        <Router>
            <Routes fallback=NotFoundPage>
                <Route path=path!("") view=DashboardPage />
                <Route path=path!("login") view=LoginPage />
            </Routes>
        </Router>
    }
}

#[cfg(feature = "ssr")]
pub fn shell(options: leptos::config::LeptosOptions) -> impl IntoView {
    view! {
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <AutoReload options=options.clone() />
                <HydrationScripts options />
                <leptos_meta::MetaTags />
            </head>
            <body><App /></body>
        </html>
    }
}
