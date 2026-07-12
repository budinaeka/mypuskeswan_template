const API_URL = 'api/index.php';
const FOTO_API = 'api/foto_lampiran.php';

let _cache = {
    users: [],
    pengobatan: [],
    vaksinasi: [],
    monitoring: [],
    surveilans: [],
    phms: [],
    surat: [],
    stok_obat: [],
    pemakaian_obat: [],
    kegiatan_lain: [],
    kunjungan_tamu: [],
    kreasi_konten: [],
    layanan_usg: [],
    foto_lampiran: {}
};

const DB = {
    init: async () => {
        try {
            const tables = [
                'users', 'pengobatan', 'vaksinasi', 'monitoring', 
                'surveilans', 'phms', 'surat', 'stok_obat', 'pemakaian_obat', 
                'kegiatan_lain', 'kunjungan_tamu', 'kreasi_konten', 'layanan_usg'
            ];

            const promises = tables.map(table => DB.fetchTable(table));
            await Promise.all(promises);
            await DB.loadAllFotos();
            console.log('Database initialized and data loaded');
        } catch (error) {
            console.error('Failed to initialize database:', error);
        }
    },

    // Lightweight reload for dashboard - refresh tables + fotos
    reload: async () => {
        try {
            const tables = [
                'pengobatan', 'vaksinasi', 'monitoring', 
                'surveilans', 'kegiatan_lain', 'kunjungan_tamu', 'layanan_usg'
            ];
            const promises = tables.map(table => DB.fetchTable(table));
            await Promise.all(promises);
            // Reset foto cache and reload
            _cache.foto_lampiran = {};
            await DB.loadAllFotos();
        } catch (error) {
            console.error('Failed to reload:', error);
        }
    },

    fetchTable: async (table) => {
        try {
            // Add timestamp to prevent browser caching of the request itself
            const res = await fetch(`${API_URL}?table=${table}&_t=${Date.now()}`);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();
            _cache[table] = Array.isArray(data) ? data : [];
            return _cache[table];
        } catch (err) {
            console.error(`Error loading ${table}:`, err);
            _cache[table] = [];
            return [];
        }
    },

    get: () => {
        return _cache;
    },

    // Helpers
    getTable: (table) => {
        return _cache[table] || [];
    },

    addToTable: async (table, item) => {
        if (!_cache[table]) _cache[table] = [];
        const tempId = Date.now();
        const payload = { ...item };
        const localItem = { ...payload, id: tempId, created_at: new Date().toISOString() };
        _cache[table].push(localItem);

        try {
            const res = await fetch(`${API_URL}?table=${table}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const savedItem = await res.json();
            const index = _cache[table].findIndex(i => i.id === tempId);
            if (index !== -1) {
                _cache[table][index] = { ..._cache[table][index], ...savedItem };
                return _cache[table][index];
            }
        } catch (err) {
            console.error(`Error adding to ${table}:`, err);
        }
        return localItem;
    },

    updateInTable: (table, id, updates) => {
        if (!_cache[table]) return false;
        const index = _cache[table].findIndex(i => i.id == id);
        if (index !== -1) {
            // Optimistic update
            _cache[table][index] = { ..._cache[table][index], ...updates };

            fetch(`${API_URL}?table=${table}&id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updates)
            }).catch(err => console.error(`Error updating ${table}:`, err));

            return true;
        }
        return false;
    },

    deleteFromTable: (table, id) => {
        if (!_cache[table]) return false;
        
        // Optimistic update
        const prevData = [..._cache[table]];
        _cache[table] = _cache[table].filter(i => i.id != id);

        fetch(`${API_URL}?table=${table}&id=${id}`, {
            method: 'DELETE'
        }).catch(err => {
            console.error(`Error deleting from ${table}:`, err);
            // Revert if failed (optional, but good practice)
            _cache[table] = prevData;
        });

        return true;
    },

    // Nested helpers (e.g., vaksinasi.pmk)
    // In MySQL version, these are flat tables with a 'type' column.
    // So 'vaksinasi' table has 'type' column.
    
    getNested: (parent, child) => {
        // parent is table name (e.g., 'vaksinasi')
        // child is type value (e.g., 'pmk')
        const rows = _cache[parent] || [];
        return rows.filter(r => r.type === child);
    },

    addToNested: async (parent, child, item) => {
        if (!_cache[parent]) _cache[parent] = [];
        const payload = { ...item, type: child };
        const tempId = Date.now();
        const localItem = { ...payload, id: tempId, created_at: new Date().toISOString() };
        _cache[parent].push(localItem);

        try {
            const res = await fetch(`${API_URL}?table=${parent}&type=${child}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const savedItem = await res.json();
            const index = _cache[parent].findIndex(i => i.id === tempId);
            if (index !== -1) {
                _cache[parent][index] = { ..._cache[parent][index], ...savedItem };
                return _cache[parent][index];
            }
        } catch (err) {
            console.error(`Error adding to ${parent}/${child}:`, err);
        }
        return localItem;
    },

    updateNested: (parent, child, id, updates) => {
        if (!_cache[parent]) return false;
        const index = _cache[parent].findIndex(i => i.id == id && i.type === child);
        if (index !== -1) {
            _cache[parent][index] = { ..._cache[parent][index], ...updates };

            fetch(`${API_URL}?table=${parent}&id=${id}&type=${child}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updates)
            }).catch(err => console.error(`Error updating ${parent}/${child}:`, err));

            return true;
        }
        return false;
    },

    deleteFromNested: (parent, child, id) => {
        if (!_cache[parent]) return false;
        
        const prevData = [..._cache[parent]];
        _cache[parent] = _cache[parent].filter(i => !(i.id == id && i.type === child));

        fetch(`${API_URL}?table=${parent}&id=${id}&type=${child}`, {
            method: 'DELETE'
        }).catch(err => {
            console.error(`Error deleting from ${parent}/${child}:`, err);
            _cache[parent] = prevData;
        });

        return true;
    },

    // ============================================
    // Foto Lampiran Helpers
    // ============================================
    loadAllFotos: async () => {
        try {
            const res = await fetch(`${FOTO_API}?bulk=1&_t=${Date.now()}`);
            if (res.ok) {
                const allFotos = await res.json();
                if (Array.isArray(allFotos)) {
                    // Group by fitur_record_id
                    _cache.foto_lampiran = {};
                    for (const f of allFotos) {
                        const key = `${f.fitur}_${f.record_id}`;
                        if (!_cache.foto_lampiran[key]) _cache.foto_lampiran[key] = [];
                        _cache.foto_lampiran[key].push(f);
                    }
                    console.log(`Loaded ${allFotos.length} fotos in ${Object.keys(_cache.foto_lampiran).length} groups`);
                }
            }
        } catch(e) {
            console.error('Error loading fotos:', e);
        }
    },

    getFotos: (feature, recordId) => {
        return _cache.foto_lampiran[`${feature}_${recordId}`] || [];
    },

    uploadFotos: async (feature, recordId, files, tanggal) => {
        const formData = new FormData();
        formData.append('fitur', feature);
        formData.append('record_id', recordId);
        formData.append('tanggal', tanggal || '');
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        try {
            const res = await fetch(FOTO_API, { method: 'POST', body: formData });
            const result = await res.json();
            if (result.uploaded && result.uploaded.length > 0) {
                const key = `${feature}_${recordId}`;
                if (!_cache.foto_lampiran[key]) _cache.foto_lampiran[key] = [];
                _cache.foto_lampiran[key].push(...result.uploaded);
            }
            return result;
        } catch (err) {
            console.error('Error uploading fotos:', err);
            return { uploaded: [], errors: [err.message] };
        }
    },

    deleteFoto: async (photoId) => {
        try {
            const res = await fetch(`${FOTO_API}?id=${photoId}`, { method: 'DELETE' });
            const result = await res.json();
            if (result.success) {
                // Remove from cache
                for (const key in _cache.foto_lampiran) {
                    _cache.foto_lampiran[key] = _cache.foto_lampiran[key].filter(f => f.id != photoId);
                }
            }
            return result;
        } catch (err) {
            console.error('Error deleting foto:', err);
            return { success: false };
        }
    }
};
