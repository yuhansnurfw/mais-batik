// api/products.js - Handler Produk untuk Vercel Serverless
const { query } = require("./db");

function checkAdmin(req) {
  const authKey = req.headers["x-admin-key"] || req.query.key;
  const expectedPassword = process.env.ADMIN_PASSWORD || "admin123";
  return authKey === expectedPassword || (authKey && authKey.startsWith("sb_"));
}

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, DELETE, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, x-admin-key");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  try {
    // 1. GET: Ambil daftar produk
    if (req.method === "GET") {
      const all = req.query.all === "true" || req.query.all === "1";
      const statusFilter = all ? "" : "WHERE p.status = 'active'";

      const rows = await query(`
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM public.products p
        LEFT JOIN public.categories c ON c.id = p.category_id
        ${statusFilter}
        ORDER BY p.id ASC
      `);

      if (rows !== null) {
        return res.status(200).json(rows);
      }

      // Fallback via Supabase REST
      const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
      const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
      const filter = all ? "" : "&status=eq.active";
      const restRes = await fetch(`${sbUrl}/rest/v1/products?select=*,categories(name,slug)${filter}&order=id.asc`, {
        headers: {
          apikey: sbKey,
          Authorization: `Bearer ${sbKey}`
        }
      });
      const data = await restRes.json();
      return res.status(restRes.status).json(data);
    }

    // 2. POST: Tambah produk baru (admin)
    if (req.method === "POST") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const { name, category_id, variant, image_path, price_min, price_max, stock, status } = body;

      if (!name || !category_id) {
        return res.status(400).json({ error: "Nama produk dan kategori wajib diisi." });
      }

      const rows = await query(
        `INSERT INTO public.products 
         (name, category_id, variant, image_path, price_min, price_max, stock, status)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
         RETURNING *`,
        [
          name,
          Number(category_id),
          variant || "-",
          image_path || "assets/images/placeholder.jpg",
          Number(price_min) || 0,
          Number(price_max) || Number(price_min) || 0,
          Number(stock) || 0,
          status || "active"
        ]
      );

      if (rows !== null) {
        return res.status(201).json({ success: true, product: rows[0] });
      }

      // Fallback REST
      const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
      const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
      await fetch(`${sbUrl}/rest/v1/products`, {
        method: "POST",
        headers: {
          apikey: sbKey,
          Authorization: `Bearer ${sbKey}`,
          "Content-Type": "application/json",
          Prefer: "return=minimal"
        },
        body: JSON.stringify({
          name,
          category_id: Number(category_id),
          variant: variant || "-",
          image_path: image_path || "assets/images/placeholder.jpg",
          price_min: Number(price_min) || 0,
          price_max: Number(price_max) || Number(price_min) || 0,
          stock: Number(stock) || 0,
          status: status || "active"
        })
      });
      return res.status(201).json({ success: true });
    }

    // 3. DELETE: Hapus produk (admin)
    if (req.method === "DELETE") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const id = body.id || req.query.id;
      if (!id) {
        return res.status(400).json({ error: "ID produk diperlukan." });
      }

      const rows = await query("DELETE FROM public.products WHERE id = $1 RETURNING id", [Number(id)]);
      if (rows !== null) {
        return res.status(200).json({ success: true });
      }

      const sbUrl = (process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || "").replace(/\/+$/, "");
      const sbKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;
      await fetch(`${sbUrl}/rest/v1/products?id=eq.${id}`, {
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
    console.error("Products API Error:", err);
    return res.status(500).json({ error: err.message || "Internal server error" });
  }
};
