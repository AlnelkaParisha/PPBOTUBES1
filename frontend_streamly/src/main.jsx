import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import api, { getData, apiMessage } from './api';
import './style.css';

const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
const titleCase = (value = '') => String(value).replaceAll('_', ' ').replace(/\\b\\w/g, c => c.toUpperCase());
const contentType = (value = '') => String(value).toLowerCase().replaceAll('_', '').replaceAll('-', '');
const planDescription = (plan, index, allPlans) => {
  const name = String(plan.name || '').toLowerCase();
  const sorted = [...allPlans].sort((a, b) => Number(a.price || 0) - Number(b.price || 0));
  const rank = sorted.findIndex(p => p.id === plan.id);
  const quality = plan.video_quality || plan.resolution || plan.quality;    
  const devices = plan.max_devices || plan.device_limit || plan.devices;
  const features = [];
  if (quality) features.push(`Kualitas video: ${quality}`);
  if (devices) features.push(`Maksimal ${devices} perangkat`);
  if (plan.discount_percent) features.push(`Diskon ${plan.discount_percent}%`);
  if (plan.billing_cycle) features.push(`Tagihan ${titleCase(plan.billing_cycle)}`);
  if (features.length) return features.join(' • ');
  if (/basic|starter|standard|premium|family|pro/.test(name)) {
    if (/basic|starter/.test(name)) return 'Pilihan paket dasar untuk menikmati layanan Streamly.';
    if (/standard/.test(name)) return 'Paket dengan pilihan layanan yang lebih lengkap.';
    if (/premium|family|pro/.test(name)) return 'Paket dengan manfaat lebih tinggi sesuai ketentuan paket.';
  }
  if (allPlans.length > 1) {
    if (rank === 0) return 'Paket dasar dengan harga paling terjangkau.';
    if (rank === allPlans.length - 1) return 'Paket dengan harga tertinggi dan pilihan layanan paling lengkap yang tersedia.';
    return 'Paket menengah dengan harga dan manfaat di antara paket lainnya.';
  }
  return 'Paket berlangganan Streamly. Detail manfaat mengikuti informasi paket.';
};

function App() {
  const [token, setToken] = useState(localStorage.getItem('streamly_token'));
  const [user, setUser] = useState(null);
  const [page, setPage] = useState('home');
  const [plans, setPlans] = useState([]);
  const [contents, setContents] = useState([]);
  const [subscriptions, setSubscriptions] = useState([]);
  const [invoices, setInvoices] = useState([]);
  const [selected, setSelected] = useState(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);
  const [notice, setNotice] = useState('');
  const [error, setError] = useState('');

  const showError = (err) => { setError(apiMessage(err)); setNotice(''); };
  const showNotice = (msg) => { setNotice(msg); setError(''); };

  async function loadPublic() {
    try {
      const p = await api.get('/plans'); setPlans(getData(p) || []);
    } catch (err) { showError(err); }
  }
  async function loadPrivate() {
    if (!token) return;
    setLoading(true);
    try {
      const [me, c, s, i] = await Promise.all([
        api.get('/me'), api.get('/contents'), api.get('/subscriptions'), api.get('/invoices')
      ]);
      setUser(getData(me)); setContents(getData(c) || []); setSubscriptions(getData(s) || []); setInvoices(getData(i) || []);
    } catch (err) { showError(err); }
    finally { setLoading(false); }
  }
  useEffect(() => { loadPublic(); }, []);
  useEffect(() => { if (token) loadPrivate(); else { setUser(null); setContents([]); setSubscriptions([]); setInvoices([]); } }, [token]);

  async function login(email, password, register = false, name = '', passwordConfirmation = '') {
    try {
      const response = await api.post(register ? '/register' : '/login', register ? { name, email, password, password_confirmation: passwordConfirmation } : { email, password });
      const data = getData(response);
      localStorage.setItem('streamly_token', data.token);
      setToken(data.token); setUser(data.user); setPage('home'); showNotice(response.data.message || 'Berhasil masuk.');
    } catch (err) { showError(err); }
  }
  async function logout() {
    try { await api.post('/logout'); } catch { /* token may already be expired */ }
    localStorage.removeItem('streamly_token'); setToken(null); setUser(null); setPage('home'); showNotice('Anda telah logout.');
  }
  async function subscribe(plan, members = []) {
    if (!token) { setPage('login'); showNotice('Silakan login terlebih dahulu.'); return; }
    try {
      const response = await api.post('/subscriptions', { plan_id: plan.id, ...(members.length ? { members } : {}) });
      showNotice(response.data.message || 'Subscription berhasil dibuat.'); await loadPrivate(); setPage('subscriptions');
    } catch (err) { showError(err); }
  }
  async function pay(invoice, method, simulate = 'success') {
    try {
      const response = await api.post(`/invoices/${invoice.id}/pay`, { payment_method: method, simulate });
      showNotice(response.data.message || 'Pembayaran berhasil.'); await loadPrivate();
    } catch (err) { showError(err); }
  }
  async function cancelSubscription(subscription) {
    if (!confirm('Batalkan subscription ini?')) return;
    try { const r = await api.post(`/subscriptions/${subscription.id}/cancel`); showNotice(r.data.message); await loadPrivate(); }
    catch (err) { showError(err); }
  }
  async function changePlan(subscription, plan) {
    try {
      const preview = await api.post(`/subscriptions/${subscription.id}/preview-change`, { plan_id: plan.id });
      const previewData = getData(preview);
      const confirmChange = confirm(`Perubahan paket\n${subscription.plan?.name} → ${plan.name}\n\nPreview: ${JSON.stringify(previewData, null, 2)}\n\nLanjutkan?`);
      if (!confirmChange) return;
      const r = await api.post(`/subscriptions/${subscription.id}/change-plan`, { plan_id: plan.id });
      showNotice(r.data.message); await loadPrivate();
    } catch (err) { showError(err); }
  }

  const activeSubscription = subscriptions.find(s => ['active', 'pending'].includes(s.status));
  const filteredContents = contents.filter(c => c.title?.toLowerCase().includes(search.toLowerCase()));
  const movies = filteredContents.filter(c => ['movie', 'film'].includes(contentType(c.type)));
  const series = filteredContents.filter(c => ['series', 'tvseries', 'tvshow'].includes(contentType(c.type)));

  return <div className="app">
    <header className="navbar">
      <div className="brand" onClick={() => setPage('home')}>Streamly</div>
      <nav>
        <button className={page === 'home' ? 'active' : ''} onClick={() => setPage('home')}>Home</button>
        <button className={page === 'movies' ? 'active' : ''} onClick={() => setPage('movies')}>Movies</button>
        <button className={page === 'series' ? 'active' : ''} onClick={() => setPage('series')}>Series</button>
        <button className={page === 'plans' ? 'active' : ''} onClick={() => setPage('plans')}>Plans</button>
        {token && <button className={page === 'subscriptions' ? 'active' : ''} onClick={() => setPage('subscriptions')}>My Subscription</button>}
        {token && <button className={page === 'invoices' ? 'active' : ''} onClick={() => setPage('invoices')}>Invoices</button>}
      </nav>
      <div className="nav-right">
        <input className="search" placeholder="Cari film atau series..." value={search} onChange={e => setSearch(e.target.value)} />
        {token ? <><span className="user-name">{user?.name || 'User'}</span><button className="logout" onClick={logout}>Logout</button></> : <button className="login-btn" onClick={() => setPage('login')}>Login</button>}
      </div>
    </header>

    <main className="container">
      {(notice || error) && <div className={`alert ${error ? 'error' : ''}`}>{error || notice}<button onClick={() => { setError(''); setNotice(''); }}>×</button></div>}
      {loading && <div className="loading">Memuat data...</div>}

      {page === 'login' && <AuthForm onSubmit={login} />}
      {page === 'home' && <>
        <section className={`hero ${activeSubscription ? 'hero-subscribed' : ''}`}>
          <div className="hero-copy">
            <span className="small-label">{activeSubscription ? 'STATUS BERLANGGANAN' : 'STREAMING PLATFORM'}</span>
            <h1>{activeSubscription ? `Halo, ${user?.name || 'Pengguna Streamly'}!` : 'Selamat datang di Streamly'}</h1>
            <p>{activeSubscription
              ? `Kamu sedang menggunakan paket ${activeSubscription.plan?.name || 'Subscription'}. Status: ${titleCase(activeSubscription.status)}${activeSubscription.period_end ? ` • Berlaku sampai ${activeSubscription.period_end}` : ''}.`
              : 'Temukan film dan series favoritmu di satu tempat.'}</p>
            <div className="hero-buttons">
              <button className="primary" onClick={() => setPage('movies')}>{activeSubscription ? 'Jelajahi Konten' : 'Lihat Konten'}</button>
              <button className="secondary" onClick={() => setPage(activeSubscription ? 'subscriptions' : 'plans')}>{activeSubscription ? 'Kelola Subscription' : 'Pilih Paket'}</button>
            </div>
          </div>
          <div className="hero-illustration"><div className="screen">▶</div><div className="popcorn">🍿</div></div>
        </section>
        <ContentRow title="Film Populer" items={movies.slice(0, 5)} onSelect={setSelected} onSeeAll={() => setPage('movies')} />
        <ContentRow title="Series Terbaru" items={series.slice(0, 5)} onSelect={setSelected} onSeeAll={() => setPage('series')} />
        {!token && <section className="login-reminder"><h3>Login untuk mengakses konten</h3><p>Konten dan fitur streaming tersedia setelah login.</p><button className="primary" onClick={() => setPage('login')}>Login Sekarang</button></section>}
      </>}
      {page === 'movies' && <ContentGrid title="Movies" items={movies} onSelect={setSelected} />}
      {page === 'series' && <ContentGrid title="Series" items={series} onSelect={setSelected} />}
      {page === 'plans' && <Plans plans={plans} onSubscribe={subscribe} token={token} />}
      {page === 'subscriptions' && <Subscriptions items={subscriptions} plans={plans} onCancel={cancelSubscription} onChangePlan={changePlan} />}
      {page === 'invoices' && <Invoices items={invoices} onPay={pay} />}
      {selected && <ContentModal content={selected} onClose={() => setSelected(null)} onWatch={async () => {
        if (!token) { setSelected(null); setPage('login'); return; }
        try { const r = await api.get(`/contents/${selected.id}/watch`); const data = getData(r); alert(`Akses menonton berhasil!\nURL: ${data.stream_url}`); }
        catch (err) { showError(err); }
      }} />}
    </main>
    <footer>Streamly — Demo aplikasi streaming subscription</footer>
  </div>;
}

function AuthForm({ onSubmit }) {
  const [register, setRegister] = useState(false); const [name, setName] = useState(''); const [email, setEmail] = useState(''); const [password, setPassword] = useState(''); const [confirmPassword, setConfirmPassword] = useState(''); const [formError, setFormError] = useState('');
  return <div className="auth-box"><h2>{register ? 'Register' : 'Login'}</h2><p>{register ? 'Buat akun Streamly' : 'Masuk ke akun Streamly'}</p><form onSubmit={e => { e.preventDefault(); if (register && password !== confirmPassword) { setFormError('Password dan konfirmasi password tidak sama.'); return; } setFormError(''); onSubmit(email, password, register, name, confirmPassword); }}>
    {register && <label>Nama<input required value={name} onChange={e => setName(e.target.value)} /></label>}
    <label>Email<input type="email" required value={email} onChange={e => setEmail(e.target.value)} /></label><label>Password<input type="password" minLength={register ? 8 : undefined} required value={password} onChange={e => setPassword(e.target.value)} /></label>{register && <label>Konfirmasi Password<input type="password" minLength={8} required value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)} /></label>}{formError && <p className="form-error">{formError}</p>}
    <button className="primary full">{register ? 'Register' : 'Login'}</button></form><button className="text-button" onClick={() => setRegister(!register)}>{register ? 'Sudah punya akun? Login' : 'Belum punya akun? Register'}</button></div>;
}
function ContentRow({ title, items, onSelect, onSeeAll }) { return <section className="content-section"><div className="section-heading"><h2>{title}</h2><button onClick={onSeeAll}>Lihat Semua</button></div><div className="poster-row">{items.length ? items.map(item => <Poster key={item.id} item={item} onClick={() => onSelect(item)} />) : <div className="empty-content"><strong>Belum ada {title.toLowerCase()}.</strong><p>Konten akan ditampilkan di sini setelah tersedia dari backend.</p></div>}</div></section>; }
function ContentGrid({ title, items, onSelect }) { return <section><h2 className="page-title">{title}</h2><div className="poster-grid">{items.map(item => <Poster key={item.id} item={item} onClick={() => onSelect(item)} />)}{!items.length && <p className="muted">Konten belum tersedia atau silakan login.</p>}</div></section>; }
function Poster({ item, onClick }) { const isSeries = ['series','tvseries','tvshow'].includes(contentType(item.type)); const palette = isSeries ? ['#373052','#3d5943','#59616b','#5b4a3d','#3b3949'] : ['#2b455d','#315166','#333944','#684b37','#bd8b50']; const color = palette[(Number(item.id)||0) % palette.length]; return <button className="poster" onClick={onClick}><div className="poster-image" style={{background: `linear-gradient(135deg, ${color}, #24212d)`}}><div className="poster-placeholder">{isSeries ? 'SERIES' : 'FILM'}</div><span className="poster-name">{item.title}</span></div></button>; }
function Plans({ plans, onSubscribe, token }) { return <section><h2 className="page-title">Pilihan Paket Langganan</h2><p className="page-subtitle">Bandingkan paket berdasarkan harga dan informasi yang tersedia. Detail manfaat mengikuti data paket dari backend.</p><div className="plan-grid">{plans.map((plan, index) => <div className="plan-card" key={plan.id}><h3>{plan.name}</h3><div className="price">{money(plan.price)}</div><p className="plan-cycle">{titleCase(plan.billing_cycle)}{plan.discount_percent ? ` • Diskon ${plan.discount_percent}%` : ''}</p><p className="plan-description">{planDescription(plan, index, plans)}</p><button className="primary full" onClick={() => onSubscribe(plan)}>{token ? 'Pilih Paket' : 'Login untuk Berlangganan'}</button></div>)}</div>{!plans.length && <p className="muted">Paket belum tersedia.</p>}</section>; }
function Subscriptions({ items, plans, onCancel, onChangePlan }) { return <section><h2 className="page-title">My Subscription</h2>{items.map(s => <div className="data-card" key={s.id}><div><h3>{s.plan?.name || 'Subscription'}</h3><p>Status: <span className="status">{titleCase(s.status)}</span></p><p>Periode: {s.period_start} – {s.period_end}</p><p>Saldo kredit: {money(s.credit_balance)}</p></div><div className="actions"><select defaultValue="" onChange={e => { const p = plans.find(x => String(x.id) === e.target.value); if (p) onChangePlan(s, p); }}><option value="">Ubah paket...</option>{plans.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}</select>{s.status === 'active' && <button className="secondary" onClick={() => onCancel(s)}>Cancel Subscription</button>}</div></div>)}{!items.length && <p className="muted">Belum memiliki subscription. Silakan pilih paket.</p>}</section>; }
function Invoices({ items, onPay }) { return <section><h2 className="page-title">Invoice</h2>{items.map(inv => <div className="data-card invoice" key={inv.id}><div><h3>{inv.invoice_number || `Invoice #${inv.id}`}</h3><p>{titleCase(inv.type)} • {inv.due_date}</p><p>Status: <span className="status">{titleCase(inv.status)}</span></p><strong>{money(inv.total)}</strong></div>{inv.status !== 'paid' && <div className="actions"><select id={`method-${inv.id}`} defaultValue="bank_transfer"><option value="bank_transfer">Bank Transfer</option><option value="ewallet">E-Wallet</option><option value="qris">QRIS</option><option value="credit_card">Credit Card</option></select><button className="primary" onClick={() => onPay(inv, document.getElementById(`method-${inv.id}`).value)}>Bayar (Simulasi)</button></div>}</div>)}{!items.length && <p className="muted">Belum ada invoice.</p>}</section>; }
function ContentModal({ content, onClose, onWatch }) { return <div className="modal-backdrop" onClick={onClose}><div className="modal" onClick={e => e.stopPropagation()}><button className="modal-close" onClick={onClose}>×</button><span className="small-label">{titleCase(content.type)}</span><h2>{content.title}</h2><p>{content.description || 'Tidak ada deskripsi.'}</p><button className="primary" onClick={onWatch}>Tonton</button></div></div>; }

createRoot(document.getElementById('root')).render(<App />);
