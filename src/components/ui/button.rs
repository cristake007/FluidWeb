use leptos::prelude::*;
use leptos_ui::clx;

// Rust/UI-style source-owned component. Additional Rust/UI components should be
// installed into this module with `ui add <component>` and committed with the app.
clx! {
    Button,
    button,
    "inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition disabled:pointer-events-none disabled:opacity-50 hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 active:scale-[0.98]"
}
