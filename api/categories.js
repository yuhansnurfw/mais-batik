// api/categories.js - Handler Kategori untuk Vercel Serverless
const { query } = require("./db");

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  try {
    const rows = await query("SELECT * FROM public.categories ORDER BY id ASC");
    if (rows !== null) {
      return res.status(200).json(rows);
    }

    // Fallback REST
    const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
    const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
    const restRes = await fetch(`${sbUrl}/rest/v1/categories?select=*&order=id.asc`, {
      headers: {
        apikey: sbKey,
        Authorization: `Bearer ${sbKey}`
      }
    });
    const data = await restRes.json();
    return res.status(restRes.status).json(data);
  } catch (err) {
    console.error("Categories API Error:", err);
    return res.status(500).json({ error: err.message || "Internal server error" });
  }
};
