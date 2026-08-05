use argon2::password_hash::SaltString;
use argon2::{Argon2, PasswordHash, PasswordHasher, PasswordVerifier};
use rand_core::{OsRng, RngCore};
use sha2::{Digest, Sha256};

pub const SESSION_COOKIE: &str = "fluid_session";

pub fn hash_password(password: &str) -> anyhow::Result<String> {
    let salt = SaltString::generate(&mut OsRng);
    let password_hash = Argon2::default()
        .hash_password(password.as_bytes(), &salt)
        .map_err(|error| anyhow::anyhow!(error.to_string()))?;

    Ok(password_hash.to_string())
}

pub fn verify_password(password: &str, encoded: &str) -> bool {
    PasswordHash::new(encoded)
        .ok()
        .and_then(|hash| {
            Argon2::default()
                .verify_password(password.as_bytes(), &hash)
                .ok()
        })
        .is_some()
}

pub fn new_session_token() -> String {
    let mut token = [0_u8; 32];
    let mut rng = OsRng;
    rng.fill_bytes(&mut token);
    hex::encode(token)
}

pub fn token_hash(token: &str) -> String {
    hex::encode(Sha256::digest(token.as_bytes()))
}

pub fn cookie_value(headers: &axum::http::HeaderMap, name: &str) -> Option<String> {
    headers
        .get(axum::http::header::COOKIE)?
        .to_str()
        .ok()?
        .split(';')
        .find_map(|part| {
            let (key, value) = part.trim().split_once('=')?;
            (key == name).then(|| value.to_owned())
        })
}
