// api/db.js - Unified Database Client for Vercel Serverless Functions
process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

function getSupabaseConfig() {
  const url = (
    process.env.SUPABASE_URL ||
    process.env.NEXT_PUBLIC_SUPABASE_URL ||
    "https://avlnhinsnjmvzzhtjoqx.supabase.co"
  ).replace(/\/+$/, "");

  const key =
    process.env.SUPABASE_SERVICE_ROLE_KEY ||
    process.env.SUPABASE_SECRET_KEY ||
    process.env.SUPABASE_ANON_KEY ||
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY ||
    "sb_publishable_pf4KLYf0_gyW3OKjWR40ow_sTP4WLRX";

  return { url, key };
}

async function supabaseFetch(path, options = {}) {
  const { url, key } = getSupabaseConfig();
  const headers = {
    apikey: key,
    Authorization: `Bearer ${key}`,
    "Content-Type": "application/json",
    ...(options.headers || {})
  };

  const res = await fetch(`${url}/rest/v1/${path}`, {
    ...options,
    headers
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message || `Supabase REST returned HTTP ${res.status}`);
  }

  if (res.status === 204) return true;
  const text = await res.text();
  return text ? JSON.parse(text) : true;
}

module.exports = {
  getSupabaseConfig,
  supabaseFetch
};
