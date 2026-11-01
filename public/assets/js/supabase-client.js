/**
 * Client Helper Supabase untuk Mais Batik (Vanilla JS)
 * Mendukung panggilan langsung ke REST API PostgREST Supabase tanpa memerlukan build step.
 */
(() => {
  const cfg = () => window.SUPABASE_CONFIG || {};

  const isConfigured = () => {
    const c = cfg();
    return Boolean(
      c.url &&
      c.anonKey &&
      !c.url.includes("GANTI_DENGAN") &&
      !c.anonKey.includes("GANTI_DENGAN")
    );
  };

  const getHeaders = (customToken) => {
    const c = cfg();
    const token = customToken || c.anonKey;
    return {
      "apikey": c.anonKey,
      "Authorization": `Bearer ${token}`,
      "Content-Type": "application/json",
      "Prefer": "return=representation"
    };
  };

  const getEndpoint = (path) => {
    const c = cfg();
    const base = c.url.replace(/\/+$/, "");
    return `${base}/rest/v1/${path}`;
  };

  window.MaisBatikDB = {
    isConfigured,

    async getCategories() {
      if (!isConfigured()) return [];
      const res = await fetch(getEndpoint("categories?select=*&order=id.asc"), {
        headers: getHeaders()
      });
      if (!res.ok) throw new Error("Gagal mengambil kategori dari Supabase.");
      return await res.json();
    },

    async getProducts() {
      if (!isConfigured()) return [];
      const res = await fetch(getEndpoint("products?select=*,categories(name,slug)&status=eq.active&order=id.asc"), {
        headers: getHeaders()
      });
      if (!res.ok) throw new Error("Gagal mengambil produk dari Supabase.");
      const rows = await res.json();
      return rows.map((p) => ({
        id: p.id,
        category_id: p.category_id,
        category_name: p.categories?.name || "Batik",
        category: p.categories?.slug || "pria",
        name: p.name,
        variant: p.variant,
        price_min: p.price_min,
        price_max: p.price_max,
        stock: p.stock,
        image_path: p.image_path,
        status: p.status
      }));
    },

    async createOrder(order) {
      if (!isConfigured()) throw new Error("Konfigurasi Supabase belum diatur.");
      const res = await fetch(getEndpoint("orders"), {
        method: "POST",
        headers: getHeaders(),
        body: JSON.stringify({
          product_id: order.product_id ? Number(order.product_id) : null,
          product_name: order.product_name,
          customer_name: order.customer_name,
          customer_email: order.customer_email,
          customer_phone: order.customer_phone,
          variant: order.variant || "",
          quantity: Number(order.quantity) || 1,
          unit_price: Number(order.unit_price) || 0,
          status: "baru",
          notes: order.notes || ""
        })
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || "Gagal menyimpan pesanan ke Supabase.");
      }
      return await res.json();
    },

    async getOrders(authKey) {
      if (!isConfigured()) return [];
      const res = await fetch(getEndpoint("orders?select=*&order=created_at.desc"), {
        headers: getHeaders(authKey)
      });
      if (!res.ok) throw new Error("Gagal mengambil pesanan.");
      return await res.json();
    },

    async updateOrderStatus(orderId, status, authKey) {
      if (!isConfigured()) throw new Error("Konfigurasi Supabase belum diatur.");
      const res = await fetch(getEndpoint(`orders?id=eq.${orderId}`), {
        method: "PATCH",
        headers: getHeaders(authKey),
        body: JSON.stringify({ status, updated_at: new Date().toISOString() })
      });
      if (!res.ok) throw new Error("Gagal memperbarui status pesanan.");
      return await res.json();
    },

    async deleteOrder(orderId, authKey) {
      if (!isConfigured()) throw new Error("Konfigurasi Supabase belum diatur.");
      const res = await fetch(getEndpoint(`orders?id=eq.${orderId}`), {
        method: "DELETE",
        headers: getHeaders(authKey)
      });
      if (!res.ok) throw new Error("Gagal menghapus pesanan.");
      return true;
    }
  };
})();
