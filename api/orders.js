// api/orders.js - Handler Pesanan untuk Vercel Serverless
const { query } = require("./db");

function checkAdmin(req) {
  const authKey = req.headers["x-admin-key"] || req.query.key;
  const expectedPassword = process.env.ADMIN_PASSWORD || "admin123";
  return authKey === expectedPassword || (authKey && authKey.startsWith("sb_"));
}

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, PATCH, DELETE, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, x-admin-key");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  try {
    // 1. GET: Ambil daftar pesanan (khusus admin)
    if (req.method === "GET") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak. Kata sandi admin diperlukan." });
      }

      // Coba lewat direct Postgres pool (Vercel POSTGRES_URL)
      const rows = await query("SELECT * FROM public.orders ORDER BY created_at DESC");
      if (rows !== null) {
        return res.status(200).json(rows);
      }

      // Fallback via Supabase REST API
      const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
      const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
      if (!sbUrl || !sbKey) {
        return res.status(500).json({ error: "Environment variable Supabase belum disetel di Vercel." });
      }

      const restRes = await fetch(`${sbUrl}/rest/v1/orders?select=*&order=created_at.desc`, {
        headers: {
          apikey: sbKey,
          Authorization: `Bearer ${sbKey}`
        }
      });
      const data = await restRes.json();
      return res.status(restRes.status).json(data);
    }

    // 2. POST: Tambah pesanan baru (publik dari storefront)
    if (req.method === "POST") {
      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const {
        product_id,
        product_name,
        customer_name,
        customer_email,
        customer_phone,
        variant,
        quantity,
        unit_price,
        notes
      } = body;

      if (!customer_name || !customer_phone) {
        return res.status(400).json({ error: "Nama dan nomor telepon pelanggan wajib diisi." });
      }

      const rows = await query(
        `INSERT INTO public.orders 
         (product_id, product_name, customer_name, customer_email, customer_phone, variant, quantity, unit_price, status, notes)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, 'baru', $9)
         RETURNING *`,
        [
          product_id ? Number(product_id) : null,
          product_name || "Batik",
          customer_name,
          customer_email || "-",
          customer_phone,
          variant || "-",
          Number(quantity) || 1,
          Number(unit_price) || 0,
          notes || ""
        ]
      );

      if (rows !== null) {
        return res.status(201).json({ success: true, order: rows[0] });
      }

      // Fallback via Supabase REST API
      const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
      const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
      const restRes = await fetch(`${sbUrl}/rest/v1/orders`, {
        method: "POST",
        headers: {
          apikey: sbKey,
          Authorization: `Bearer ${sbKey}`,
          "Content-Type": "application/json",
          Prefer: "return=minimal"
        },
        body: JSON.stringify({
          product_id: product_id ? Number(product_id) : null,
          product_name: product_name || "Batik",
          customer_name,
          customer_email: customer_email || "-",
          customer_phone,
          variant: variant || "-",
          quantity: Number(quantity) || 1,
          unit_price: Number(unit_price) || 0,
          status: "baru",
          notes: notes || ""
        })
      });

      if (!restRes.ok) {
        const err = await restRes.json().catch(() => ({}));
        return res.status(restRes.status).json({ error: err.message || "Gagal menyimpan pesanan." });
      }
      return res.status(201).json({ success: true });
    }

    // 3. PATCH: Ubah status pesanan (admin)
    if (req.method === "PATCH") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const { id, status } = body;
      if (!id || !status) {
        return res.status(400).json({ error: "ID dan status pesanan diperlukan." });
      }

      const rows = await query(
        "UPDATE public.orders SET status = $1, updated_at = NOW() WHERE id = $2 RETURNING *",
        [status, Number(id)]
      );

      if (rows !== null) {
        return res.status(200).json({ success: true, order: rows[0] });
      }

      // Fallback REST
      const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
      const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
      await fetch(`${sbUrl}/rest/v1/orders?id=eq.${id}`, {
        method: "PATCH",
        headers: {
          apikey: sbKey,
          Authorization: `Bearer ${sbKey}`,
          "Content-Type": "application/json",
          Prefer: "return=minimal"
        },
        body: JSON.stringify({ status, updated_at: new Date().toISOString() })
      });
      return res.status(200).json({ success: true });
    }

    // 4. DELETE: Hapus pesanan (admin)
    if (req.method === "DELETE") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const id = body.id || req.query.id;
      if (!id) {
        return res.status(400).json({ error: "ID pesanan diperlukan." });
      }

      const rows = await query("DELETE FROM public.orders WHERE id = $1 RETURNING id", [Number(id)]);
      if (rows !== null) {
        return res.status(200).json({ success: true });
      }

      const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
      const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
      await fetch(`${sbUrl}/rest/v1/orders?id=eq.${id}`, {
        method: "DELETE",
        headers: {
          apikey: sbKey,
          Authorization: `Bearer ${sbKey}`
        }
      });
      return res.status(200).json({ success: true });
    }

    return res.status(405).json({ error: "Method not allowed" });
  } catch (err) {
    console.error("Orders API Error:", err);
    return res.status(500).json({ error: err.message || "Internal server error" });
  }
};
