# proc-macro-error2 2.0.1 patch

This directory contains the published stable `proc-macro-error2` version 2.0.1 source.

FluidWeb changes one source line in `src/lib.rs`:

```rust
pub extern crate proc_macro;
```

Rust 1.97 reports E0365 because the original private `extern crate proc_macro;` is publicly re-exported by the crate's hidden macro-support module. Making the original import public is the compiler-prescribed compatibility fix and preserves the crate's public behavior.

Remove the local Cargo patch when a newer stable `proc-macro-error2` release includes the correction.
