/**
 * Client Helper Supabase untuk Mais Batik (Vanilla JS)
 * Mendukung panggilan langsung ke REST API PostgREST Supabase tanpa memerlukan build step.
 */
(() => {
  const cfg = () => {
    let localUrl = "";
    let localKey = "";
    let localAdmin = "";
    try {
      localUrl = localStorage.getItem("mais_batik_supabase_url") || "";
      localKey = localStorage.getItem("mais_batik_supabase_key") || "";
      localAdmin = localStorage.getItem("mais_batik_supabase_admin_key") || "";

      // Otomatis hapus cache jika masih mengarah ke project lama
      if (localUrl && (localUrl.includes("avlnhinsnjmvzzhtjoqx") || !localUrl.startsWith("http"))) {
        localStorage.removeItem("mais_batik_supabase_url");
        localStorage.removeItem("mais_batik_supabase_key");
        localStorage.removeItem("mais_batik_supabase_admin_key");
        localUrl = "";
        localKey = "";
        localAdmin = "";
      }
    } catch (_) {}

    const winCfg = window.SUPABASE_CONFIG || {};

    if (localUrl && !localUrl.startsWith("http")) {
      try { localStorage.removeItem("mais_batik_supabase_url"); } catch (_) {}
      localUrl = "";
    }
    if (localKey && !localKey.startsWith("sb_") && !localKey.startsWith("eyJ")) {
      try { localStorage.removeItem("mais_batik_supabase_key"); } catch (_) {}
      localKey = "";
    }

    const effectiveUrl = (localUrl && !localUrl.includes("GANTI_DENGAN")) ? localUrl : (winCfg.url || "");
    const effectiveKey = (localKey && !localKey.includes("GANTI_DENGAN")) ? localKey : (winCfg.anonKey || "");
    const effectiveAdmin = (localAdmin && (localAdmin.startsWith("sb_") || localAdmin.startsWith("eyJ")))
      ? localAdmin
      : (winCfg.adminKey || "");

    return {
      url: effectiveUrl,
      anonKey: effectiveKey,
      adminKey: effectiveAdmin,
    };
  };

  const isConfigured = () => {
    const c = cfg();
    return Boolean(
      c.url &&
      c.anonKey &&
      !c.url.includes("GANTI_DENGAN") &&
      !c.anonKey.includes("GANTI_DENGAN")
    );
  };

  const saveConfig = (url, anonKey, adminKey = "") => {
    try {
      if (url) localStorage.setItem("mais_batik_supabase_url", url.trim());
      if (anonKey) localStorage.setItem("mais_batik_supabase_key", anonKey.trim());
      if (adminKey) localStorage.setItem("mais_batik_supabase_admin_key", adminKey.trim());
      if (window.SUPABASE_CONFIG) {
        if (url) window.SUPABASE_CONFIG.url = url.trim();
        if (anonKey) window.SUPABASE_CONFIG.anonKey = anonKey.trim();
      }
      return true;
    } catch (_) {
      return false;
    }
  };

  const getHeaders = (customToken) => {
    const c = cfg();
    let token = customToken;
    if (!token || token === "admin123") {
      token = c.adminKey || c.anonKey;
    }
    const isServiceKey = Boolean(token && (token.startsWith("sb_secret_") || token.length > 50));
    return {
      "apikey": isServiceKey ? token : c.anonKey,
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

  const apiFetch = async (endpoint, options = {}) => {
    try {
      const res = await fetch(`/api/${endpoint}`, options);
      if (res.ok) {
        return { success: true, data: await res.json() };
      }
      const errData = await res.json().catch(() => ({}));
      return { success: false, status: res.status, error: errData.error || errData.message };
    } catch (_) {
      return { success: false, networkError: true };
    }
  };

  window.MaisBatikDB = {
    isConfigured,
    saveConfig,
    getConfig: cfg,

    async getCategories(authKey) {
      const apiRes = await apiFetch("categories");
      if (apiRes.success && Array.isArray(apiRes.data)) return apiRes.data;

      if (!isConfigured()) return [];
      const res = await fetch(getEndpoint("categories?select=*&order=id.asc"), {
        headers: getHeaders(authKey)
      });
      if (!res.ok) throw new Error(apiRes.error || "Gagal mengambil kategori.");
      return await res.json();
    },

    async getProducts(includeInactive = false, authKey) {
      const apiRes = await apiFetch(`products?all=${includeInactive ? "1" : "0"}`);
      if (apiRes.success && Array.isArray(apiRes.data)) {
        return apiRes.data.map((p) => ({
          id: p.id,
          category_id: p.category_id,
          category_name: p.category_name || p.categories?.name || "Batik",
          category: p.category_slug || p.categories?.slug || "pria",
          name: p.name,
          variant: p.variant,
          price_min: p.price_min,
          price_max: p.price_max,
          stock: p.stock,
          image_path: p.image_path,
          status: p.status
        }));
      }

      if (!isConfigured()) return [];
      const filter = includeInactive ? "" : "&status=eq.active";
      const res = await fetch(getEndpoint(`products?select=*,categories(name,slug)${filter}&order=id.asc`), {
        headers: getHeaders(authKey)
      });
      if (!res.ok) throw new Error(apiRes.error || "Gagal mengambil produk.");
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
      const apiRes = await apiFetch("orders", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(order)
      });
      if (apiRes.success) return apiRes.data;

      if (!isConfigured()) throw new Error(apiRes.error || "Gagal menyimpan pesanan.");
      const headers = getHeaders();
      headers["Prefer"] = "return=minimal";
      const res = await fetch(getEndpoint("orders"), {
        method: "POST",
        headers,
        body: JSON.stringify({
          product_id: order.product_id ? Number(order.product_id) : null,
          product_name: order.product_name,
          customer_name: order.customer_name,
          customer_email: order.customer_email || "-",
          customer_phone: order.customer_phone,
          variant: order.variant || "-",
          quantity: Number(order.quantity) || 1,
          unit_price: Number(order.unit_price) || 0,
          status: "baru",
          notes: order.notes || ""
        })
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || apiRes.error || "Gagal menyimpan pesanan.");
      }
      return { success: true };
    },

    async getOrders(authKey) {
      const apiRes = await apiFetch("orders", {
        headers: { "x-admin-key": authKey || "admin123" }
      });
      if (apiRes.success && Array.isArray(apiRes.data)) return apiRes.data;

      if (!isConfigured()) return [];
      const res = await fetch(getEndpoint("orders?select=*&order=created_at.desc"), {
        headers: getHeaders(authKey)
      });
      if (!res.ok) {
        throw new Error(apiRes.error || "Gagal mengambil pesanan.");
      }
      return await res.json();
    },

    async updateOrderStatus(orderId, status, authKey) {
      const apiRes = await apiFetch("orders", {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
          "x-admin-key": authKey || "admin123"
        },
        body: JSON.stringify({ id: orderId, status })
      });
      if (apiRes.success) return { success: true };

      if (!isConfigured()) throw new Error(apiRes.error || "Gagal memperbarui status.");
      const headers = getHeaders(authKey);
      headers["Prefer"] = "return=minimal";
      const res = await fetch(getEndpoint(`orders?id=eq.${orderId}`), {
        method: "PATCH",
        headers,
        body: JSON.stringify({ status, updated_at: new Date().toISOString() })
      });
      if (!res.ok) throw new Error(apiRes.error || "Gagal memperbarui status pesanan.");
      return { success: true };
    },

    async deleteOrder(orderId, authKey) {
      const apiRes = await apiFetch("orders", {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          "x-admin-key": authKey || "admin123"
        },
        body: JSON.stringify({ id: orderId })
      });
      if (apiRes.success) return true;

      if (!isConfigured()) throw new Error(apiRes.error || "Gagal menghapus pesanan.");
      const headers = getHeaders(authKey);
      headers["Prefer"] = "return=minimal";
      const res = await fetch(getEndpoint(`orders?id=eq.${orderId}`), {
        method: "DELETE",
        headers
      });
      if (!res.ok) throw new Error(apiRes.error || "Gagal menghapus pesanan.");
      return true;
    },

    async createProduct(product, authKey) {
      const apiRes = await apiFetch("products", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "x-admin-key": authKey || "admin123"
        },
        body: JSON.stringify(product)
      });
      if (apiRes.success) return { success: true };

      if (!isConfigured()) throw new Error(apiRes.error || "Gagal menyimpan produk.");
      const headers = getHeaders(authKey);
      headers["Prefer"] = "return=minimal";
      const res = await fetch(getEndpoint("products"), {
        method: "POST",
        headers,
        body: JSON.stringify(product)
      });
      if (!res.ok) throw new Error(apiRes.error || "Gagal menyimpan produk ke Supabase.");
      return { success: true };
    },

    async deleteProduct(productId, authKey) {
      const apiRes = await apiFetch("products", {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          "x-admin-key": authKey || "admin123"
        },
        body: JSON.stringify({ id: productId })
      });
      if (apiRes.success) return { success: true };

      if (!isConfigured()) throw new Error(apiRes.error || "Gagal menghapus produk.");
      const headers = getHeaders(authKey);
      headers["Prefer"] = "return=minimal";
      const res = await fetch(getEndpoint(`products?id=eq.${productId}`), {
        method: "DELETE",
        headers
      });
      if (!res.ok) throw new Error(apiRes.error || "Gagal menghapus produk.");
      return { success: true };
    }
  };
})();
