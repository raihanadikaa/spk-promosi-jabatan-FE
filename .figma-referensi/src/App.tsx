import { FormEvent, ReactNode, useMemo, useState } from 'react'

type Role = 'Admin' | 'HRD' | 'Atasan' | 'Pimpinan' | 'Karyawan'
type Page =
  | 'dashboard'
  | 'users'
  | 'employees'
  | 'organization'
  | 'periods'
  | 'criteria'
  | 'monitor'
  | 'calculation'
  | 'ranking'
  | 'assessments'
  | 'recommendations'
  | 'my-score'

type IconName =
  | 'grid'
  | 'users'
  | 'building'
  | 'calendar'
  | 'sliders'
  | 'clipboard'
  | 'calculator'
  | 'ranking'
  | 'check'
  | 'bell'
  | 'search'
  | 'plus'
  | 'download'
  | 'menu'
  | 'logout'
  | 'chevron'
  | 'edit'
  | 'eye'
  | 'mail'
  | 'lock'
  | 'close'
  | 'alert'

const iconPaths: Record<IconName, ReactNode> = {
  grid: <><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></>,
  users: <><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></>,
  building: <><path d="M3 21h18M6 21V5l6-2v18M18 21V9l-6-2" /><path d="M9 8v.01M9 12v.01M9 16v.01M15 12v.01M15 16v.01" /></>,
  calendar: <><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 11h18" /></>,
  sliders: <><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6" /></>,
  clipboard: <><rect x="5" y="4" width="14" height="17" rx="2" /><path d="M9 4V2h6v2M9 9h6M9 13h6M9 17h4" /></>,
  calculator: <><rect x="4" y="2" width="16" height="20" rx="2" /><path d="M8 6h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h.01M12 19h.01M16 19h.01" /></>,
  ranking: <><path d="M3 20h18M5 20v-7h4v7M10 20V4h4v16M15 20v-11h4v11" /></>,
  check: <path d="m5 12 4 4L19 6" />,
  bell: <><path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0" /></>,
  search: <><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></>,
  plus: <path d="M12 5v14M5 12h14" />,
  download: <><path d="M12 3v12M7 10l5 5 5-5M5 21h14" /></>,
  menu: <path d="M4 7h16M4 12h16M4 17h16" />,
  logout: <><path d="M10 17l5-5-5-5M15 12H3M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /></>,
  chevron: <path d="m9 18 6-6-6-6" />,
  edit: <><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z" /></>,
  eye: <><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12" /><circle cx="12" cy="12" r="3" /></>,
  mail: <><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></>,
  lock: <><rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></>,
  close: <path d="M6 6l12 12M18 6 6 18" />,
  alert: <><path d="M10.3 3.5 2.2 18a2 2 0 0 0 1.8 3h16a2 2 0 0 0 1.8-3L13.7 3.5a2 2 0 0 0-3.4 0Z" /><path d="M12 9v4M12 17h.01" /></>,
}

function Icon({ name, size = 18 }: { name: IconName; size?: number }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{iconPaths[name]}</svg>
}

function Button({ children, variant = 'primary', icon, onClick, type = 'button', disabled = false, className = '' }: { children: ReactNode; variant?: 'primary' | 'secondary' | 'ghost' | 'danger'; icon?: IconName; onClick?: () => void; type?: 'button' | 'submit'; disabled?: boolean; className?: string }) {
  return <button className={`button button--${variant} ${className}`} onClick={onClick} type={type} disabled={disabled}>{icon && <Icon name={icon} />}{children}</button>
}

function Badge({ children, tone = 'neutral' }: { children: ReactNode; tone?: 'success' | 'warning' | 'danger' | 'info' | 'neutral' }) {
  return <span className={`badge badge--${tone}`}><span className="badge__dot" />{children}</span>
}

function Field({ label, children, hint, error }: { label: string; children: ReactNode; hint?: string; error?: string }) {
  return <label className="field"><span className="field__label">{label}</span>{children}{error ? <span className="field__error">{error}</span> : hint ? <span className="field__hint">{hint}</span> : null}</label>
}

function SearchField({ value, onChange, placeholder = 'Cari data...' }: { value: string; onChange: (v: string) => void; placeholder?: string }) {
  return <div className="search-field"><Icon name="search" /><input value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder} aria-label={placeholder} /></div>
}

function Select({ value, onChange, children, label }: { value: string; onChange: (v: string) => void; children: ReactNode; label: string }) {
  return <select value={value} onChange={(e) => onChange(e.target.value)} aria-label={label}>{children}</select>
}

function Modal({ title, description, children, onClose, width = 'normal' }: { title: string; description?: string; children: ReactNode; onClose: () => void; width?: 'normal' | 'wide' }) {
  return <div className="modal-backdrop" role="presentation" onMouseDown={onClose}><div className={`modal modal--${width}`} role="dialog" aria-modal="true" aria-label={title} onMouseDown={(e) => e.stopPropagation()}><div className="modal__header"><div><h2>{title}</h2>{description && <p>{description}</p>}</div><Button variant="ghost" icon="close" onClick={onClose} className="icon-only"><span className="sr-only">Tutup</span></Button></div>{children}</div></div>
}

const criteria = [
  { code: 'C1', name: 'Masa Kerja', weight: 15, type: 'Benefit', desc: 'Akumulasi lama pengabdian karyawan' },
  { code: 'C2', name: 'Kinerja / KPI', weight: 30, type: 'Benefit', desc: 'Capaian KPI dua semester terakhir' },
  { code: 'C3', name: 'Kompetensi Teknis', weight: 25, type: 'Benefit', desc: 'Keahlian dan uji kompetensi sesuai bidang' },
  { code: 'C4', name: 'Kedisiplinan & Etika', weight: 15, type: 'Benefit', desc: 'Absensi, etika, dan kerja sama tim' },
  { code: 'C5', name: 'Pelatihan & Sertifikasi', weight: 15, type: 'Benefit', desc: 'Pelatihan, workshop, dan sertifikasi' },
]

const rankingData = [
  { rank: 1, name: 'Siti Rahma', nik: 'KRY-0024', dept: 'Teknologi Informasi', score: '0,9619', status: 'Direkomendasikan', values: ['0,8211', '1,0000', '1,0000', '0,9247', '1,0000'] },
  { rank: 2, name: 'Ahmad Fauzi', nik: 'KRY-0017', dept: 'Operasional', score: '0,9591', status: 'Direkomendasikan', values: ['0,8947', '0,9787', '0,9778', '0,9677', '0,9444'] },
  { rank: 3, name: 'Rian Pratama', nik: 'KRY-0041', dept: 'Teknologi Informasi', score: '0,9512', status: 'Direkomendasikan', values: ['0,8421', '0,9787', '0,9778', '0,9677', '0,9444'] },
  { rank: 4, name: 'Eko Prasetyo', nik: 'KRY-0032', dept: 'Keuangan', score: '0,9360', status: 'Memenuhi Syarat', values: ['0,8842', '0,9468', '0,9889', '0,9032', '0,9111'] },
  { rank: 5, name: 'Dimas Pratama', nik: 'KRY-0029', dept: 'Operasional', score: '0,9278', status: 'Memenuhi Syarat', values: ['1,0000', '0,8723', '0,9444', '1,0000', '0,8667'] },
]

const employees = [
  { nik: 'KRY-0017', name: 'Ahmad Fauzi', dept: 'Operasional', position: 'Staf Senior', supervisor: 'Budi Santoso', joined: '12-03-2018' },
  { nik: 'KRY-0024', name: 'Siti Rahma', dept: 'Teknologi Informasi', position: 'System Analyst', supervisor: 'Rizky Maulana', joined: '04-08-2019' },
  { nik: 'KRY-0029', name: 'Dimas Pratama', dept: 'Operasional', position: 'Koordinator', supervisor: 'Budi Santoso', joined: '21-01-2016' },
  { nik: 'KRY-0032', name: 'Eko Prasetyo', dept: 'Keuangan', position: 'Analis Keuangan', supervisor: 'Maya Putri', joined: '09-11-2017' },
  { nik: 'KRY-0041', name: 'Rian Pratama', dept: 'Teknologi Informasi', position: 'Software Engineer', supervisor: 'Rizky Maulana', joined: '18-06-2020' },
]

const navByRole: Record<Role, { label: string; page: Page; icon: IconName }[]> = {
  Admin: [{ label: 'Dashboard', page: 'dashboard', icon: 'grid' }, { label: 'Manajemen User', page: 'users', icon: 'users' }],
  HRD: [{ label: 'Dashboard', page: 'dashboard', icon: 'grid' }, { label: 'Data Karyawan', page: 'employees', icon: 'users' }, { label: 'Departemen & Jabatan', page: 'organization', icon: 'building' }, { label: 'Periode Penilaian', page: 'periods', icon: 'calendar' }, { label: 'Kriteria & Bobot', page: 'criteria', icon: 'sliders' }, { label: 'Monitor Assessment', page: 'monitor', icon: 'clipboard' }, { label: 'Perhitungan SAW', page: 'calculation', icon: 'calculator' }, { label: 'Hasil & Ranking', page: 'ranking', icon: 'ranking' }],
  Atasan: [{ label: 'Dashboard', page: 'dashboard', icon: 'grid' }, { label: 'Penilaian', page: 'assessments', icon: 'clipboard' }, { label: 'Hasil Bawahan', page: 'ranking', icon: 'ranking' }],
  Pimpinan: [{ label: 'Dashboard', page: 'dashboard', icon: 'grid' }, { label: 'Hasil & Ranking', page: 'ranking', icon: 'ranking' }, { label: 'Persetujuan Promosi', page: 'recommendations', icon: 'check' }],
  Karyawan: [{ label: 'Dashboard', page: 'dashboard', icon: 'grid' }, { label: 'Nilai Saya', page: 'my-score', icon: 'ranking' }],
}

const roleNames: Record<Role, { name: string; email: string }> = {
  Admin: { name: 'Dewi Anggraini', email: 'admin@spk.test' },
  HRD: { name: 'Nadia Permata', email: 'hrd@spk.test' },
  Atasan: { name: 'Rizky Maulana', email: 'manager@spk.test' },
  Pimpinan: { name: 'Arief Wicaksono', email: 'direktur@spk.test' },
  Karyawan: { name: 'Siti Rahma', email: 'karyawan@spk.test' },
}

function Login({ onLogin }: { onLogin: (role: Role) => void }) {
  const [email, setEmail] = useState('hrd@spk.test')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const submit = (e: FormEvent) => {
    e.preventDefault()
    if (!email.trim() || !password) return setError('Email dan password wajib diisi.')
    if (password.length < 8 || !/[a-zA-Z]/.test(password) || !/\d/.test(password)) return setError('Password minimal 8 karakter serta berisi huruf dan angka.')
    const role = email.includes('admin') ? 'Admin' : email.includes('manager') ? 'Atasan' : email.includes('direktur') ? 'Pimpinan' : email.includes('karyawan') ? 'Karyawan' : 'HRD'
    onLogin(role)
  }
  return <main className="login-page">
    <section className="login-story">
      <div className="brand brand--light"><span className="brand__mark"><Icon name="ranking" size={24} /></span><span>SPK Promosi</span></div>
      <div className="login-story__content">
        <span className="eyebrow eyebrow--light">Sistem pendukung keputusan</span>
        <h1>Keputusan promosi yang lebih objektif dan transparan.</h1>
        <p>Kelola penilaian, telusuri perhitungan SAW, dan tinjau rekomendasi dalam satu alur yang terstruktur.</p>
        <div className="formula-card"><span>Metode SAW</span><strong>Vᵢ = Σ (wⱼ × rᵢⱼ)</strong><small>Setiap skor dapat ditelusuri hingga nilai mentah dan bobotnya.</small></div>
      </div>
      <p className="login-story__footer">SPK Promosi Jabatan · Versi prototipe</p>
    </section>
    <section className="login-panel">
      <form className="login-form" onSubmit={submit}>
        <div className="login-form__intro"><span className="mobile-brand"><span className="brand__mark"><Icon name="ranking" /></span>SPK Promosi</span><span className="eyebrow">Selamat datang</span><h2>Masuk ke akun Anda</h2><p>Gunakan akun organisasi untuk melanjutkan.</p></div>
        {error && <div className="alert alert--danger"><Icon name="alert" /><span>{error}</span></div>}
        <Field label="Email atau username"><input value={email} onChange={(e) => { setEmail(e.target.value); setError('') }} placeholder="nama@perusahaan.co.id" autoComplete="username" /></Field>
        <Field label="Password" hint="Minimal 8 karakter, berisi huruf dan angka."><input value={password} onChange={(e) => { setPassword(e.target.value); setError('') }} type="password" placeholder="Masukkan password" autoComplete="current-password" /></Field>
        <Button type="submit" className="button--full">Masuk ke Sistem <Icon name="chevron" /></Button>
        <div className="login-note"><Icon name="lock" /><span>Akun dikunci sementara selama 15 menit setelah 5 kali percobaan gagal, sesuai acuan SRS Word.</span></div>
      </form>
    </section>
  </main>
}

function StatCard({ label, value, note, icon, tone = 'blue' }: { label: string; value: string; note: string; icon: IconName; tone?: 'blue' | 'teal' | 'amber' | 'violet' }) {
  return <article className="stat-card"><span className={`stat-card__icon stat-card__icon--${tone}`}><Icon name={icon} /></span><div><p>{label}</p><strong>{value}</strong><small>{note}</small></div></article>
}

function PageHeader({ eyebrow, title, description, actions }: { eyebrow?: string; title: string; description: string; actions?: ReactNode }) {
  return <div className="page-header"><div><div className="breadcrumb"><span>SPK Promosi</span><Icon name="chevron" size={13} /><strong>{eyebrow || title}</strong></div><h1>{title}</h1><p>{description}</p></div>{actions && <div className="page-header__actions">{actions}</div>}</div>
}

function Progress({ value, tone = 'blue' }: { value: number; tone?: 'blue' | 'teal' | 'amber' }) {
  return <div className="progress" aria-label={`${value}%`}><span className={`progress__fill progress__fill--${tone}`} style={{ width: `${value}%` }} /></div>
}

function Dashboard({ role, goTo }: { role: Role; goTo: (page: Page) => void }) {
  if (role === 'Admin') return <><PageHeader title="Dashboard Admin" description="Ringkasan pengguna dan status operasional sistem." /><div className="stats-grid"><StatCard label="Total User" value="48" note="Seluruh akun terdaftar" icon="users" /><StatCard label="User Aktif" value="44" note="91,7% dari total user" icon="check" tone="teal" /><StatCard label="Role Tetap" value="5" note="Termasuk Karyawan opsional" icon="lock" tone="violet" /><StatCard label="Status Sistem" value="Normal" note="Seluruh layanan berjalan" icon="grid" tone="teal" /></div><div className="content-grid content-grid--2"><section className="card"><div className="card__header"><div><h2>Distribusi Pengguna</h2><p>Jumlah akun aktif berdasarkan role</p></div></div><div className="role-bars">{[['HRD', 8, 18], ['Atasan', 21, 48], ['Pimpinan', 4, 9], ['Karyawan', 10, 23]].map(([n, v, p]) => <div className="role-bar" key={n}><div><span>{n}</span><strong>{v} user</strong></div><Progress value={Number(p)} /></div>)}</div></section><section className="card"><div className="card__header"><div><h2>Status Sistem</h2><p>Pemeriksaan layanan utama</p></div><Badge tone="success">Normal</Badge></div><div className="status-list">{['Autentikasi & RBAC', 'Basis data', 'Layanan notifikasi'].map((x) => <div key={x}><span className="status-check"><Icon name="check" /></span><span>{x}</span><Badge tone="success">Aktif</Badge></div>)}</div></section></div></>
  if (role === 'HRD') return <><PageHeader title="Dashboard HRD" description="Pantau progres penilaian Semester 1 2026 dan lanjutkan pekerjaan prioritas." actions={<Button onClick={() => goTo('monitor')} icon="clipboard">Monitor Penilaian</Button>} /><div className="period-hero"><div><Badge tone="info">Periode aktif</Badge><h2>Semester 1 2026</h2><p>01 Januari – 30 Juni 2026</p></div><div className="period-hero__progress"><div><span>Progres assessment</span><strong>76%</strong></div><Progress value={76} tone="teal" /><small>38 dari 50 karyawan sudah mengirim penilaian</small></div></div><div className="stats-grid stats-grid--3"><StatCard label="Karyawan Dinilai" value="38 / 50" note="12 assessment belum dikirim" icon="users" tone="teal" /><StatCard label="Status Kalkulasi" value="Belum dijalankan" note="Menunggu kelengkapan assessment" icon="calculator" tone="amber" /><StatCard label="Total Kriteria" value="5" note="Total bobot terverifikasi 100%" icon="sliders" tone="violet" /></div><div className="content-grid content-grid--2"><section className="card"><div className="card__header"><div><h2>Aksi Cepat</h2><p>Lanjutkan tahapan proses penilaian</p></div></div><div className="quick-actions"><button onClick={() => goTo('periods')}><span className="quick-actions__icon"><Icon name="calendar" /></span><span><strong>Buka / Tutup Periode</strong><small>Kelola siklus penilaian</small></span><Icon name="chevron" /></button><button onClick={() => goTo('criteria')}><span className="quick-actions__icon"><Icon name="sliders" /></span><span><strong>Kelola Kriteria</strong><small>Periksa kriteria dan bobot</small></span><Icon name="chevron" /></button><button onClick={() => goTo('calculation')}><span className="quick-actions__icon"><Icon name="calculator" /></span><span><strong>Hitung Nilai SAW</strong><small>Jalankan setelah data lengkap</small></span><Icon name="chevron" /></button></div></section><section className="card"><div className="card__header"><div><h2>Aktivitas Penilaian</h2><p>Pembaruan assessment terbaru</p></div></div><div className="activity-list">{[['Siti Rahma', 'Nilai dikirim oleh Rizky Maulana', '10 menit lalu'], ['Ahmad Fauzi', 'Draf diperbarui oleh Budi Santoso', '34 menit lalu'], ['Eko Prasetyo', 'Nilai dikirim oleh Maya Putri', '1 jam lalu']].map((x) => <div key={x[0]}><span className="avatar">{x[0].split(' ').map(y => y[0]).join('')}</span><span><strong>{x[0]}</strong><small>{x[1]}</small></span><time>{x[2]}</time></div>)}</div></section></div></>
  if (role === 'Atasan') return <><PageHeader title="Dashboard Atasan" description="Selesaikan penilaian bawahan langsung sebelum periode ditutup." actions={<Button onClick={() => goTo('assessments')}>Buka Penilaian</Button>} /><div className="deadline-banner"><span><Icon name="calendar" /></span><div><strong>Batas pengisian: 30 Juni 2026</strong><p>Tersisa 12 hari untuk menyelesaikan seluruh penilaian.</p></div><Badge tone="warning">Periode berjalan</Badge></div><div className="stats-grid stats-grid--3"><StatCard label="Total Bawahan" value="8" note="Bawahan langsung" icon="users" /><StatCard label="Sudah Dinilai" value="5" note="62,5% selesai" icon="check" tone="teal" /><StatCard label="Perlu Diselesaikan" value="3" note="1 draf, 2 belum dinilai" icon="clipboard" tone="amber" /></div><section className="card"><div className="card__header"><div><h2>Penilaian Perlu Ditindaklanjuti</h2><p>Prioritaskan assessment yang belum dikirim</p></div><Button variant="secondary" onClick={() => goTo('assessments')}>Lihat Semua</Button></div><EmployeeAssessmentTable compact onAssess={() => goTo('assessments')} /></section></>
  if (role === 'Pimpinan') return <><PageHeader title="Dashboard Pimpinan" description="Tinjau kandidat teratas dan rekomendasi yang menunggu keputusan." actions={<Button onClick={() => goTo('recommendations')}>Tinjau Rekomendasi</Button>} /><div className="stats-grid stats-grid--3"><StatCard label="Kandidat Dinilai" value="50" note="Semester 1 2026" icon="users" /><StatCard label="Direkomendasikan" value="3" note="Sesuai ambang BR-08" icon="ranking" tone="teal" /><StatCard label="Menunggu Persetujuan" value="3" note="Perlu keputusan pimpinan" icon="clipboard" tone="amber" /></div><section className="card"><div className="card__header"><div><h2>Kandidat Promosi Teratas</h2><p>Hasil perhitungan SAW Semester 1 2026</p></div><Button variant="secondary" onClick={() => goTo('ranking')}>Lihat Ranking</Button></div><div className="podium">{rankingData.slice(0, 3).map((r, i) => <article key={r.name} className={i === 0 ? 'podium__item podium__item--first' : 'podium__item'}><span className="podium__rank">#{r.rank}</span><span className="avatar avatar--large">{r.name.split(' ').map(x => x[0]).join('')}</span><h3>{r.name}</h3><p>{r.dept}</p><strong>{r.score}</strong><Badge tone="success">Direkomendasikan</Badge></article>)}</div></section></>
  return <><PageHeader title="Dashboard Karyawan" description="Ringkasan hasil penilaian milik Anda. Portal ini merupakan fitur opsional." actions={<Button onClick={() => goTo('my-score')}>Lihat Nilai Saya</Button>} /><div className="optional-banner"><Icon name="alert" /><span><strong>Portal Karyawan — fitur opsional</strong><small>Hasil hanya ditampilkan setelah periode ditutup.</small></span></div><div className="score-hero"><div><span>Skor akhir Semester 2 2025</span><strong>0,9619</strong><Badge tone="success">Direkomendasikan</Badge></div><div><span>Peringkat</span><strong>#1</strong><small>dari 47 karyawan</small></div></div></>
}

function DataToolbar({ search, setSearch, filter, setFilter, filterOptions, action }: { search: string; setSearch: (v: string) => void; filter: string; setFilter: (v: string) => void; filterOptions: string[]; action?: ReactNode }) {
  return <div className="toolbar"><div className="toolbar__filters"><SearchField value={search} onChange={setSearch} /><Select value={filter} onChange={setFilter} label="Filter data"><option value="">Semua</option>{filterOptions.map(x => <option key={x}>{x}</option>)}</Select></div>{action}</div>
}

function Pagination({ count = 50 }: { count?: number }) {
  return <div className="pagination"><span>Menampilkan 1–5 dari {count} data</span><div><Button variant="ghost" disabled>Sebelumnya</Button><button className="page-number page-number--active">1</button><button className="page-number">2</button><Button variant="ghost">Berikutnya</Button></div></div>
}

function UsersPage({ notify }: { notify: (msg: string) => void }) {
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('')
  const [tab, setTab] = useState<'user' | 'role'>('user')
  const [modal, setModal] = useState(false)
  const rows = [
    ['Dewi Anggraini', 'admin@spk.test', 'Admin', 'Aktif'],
    ['Nadia Permata', 'hrd@spk.test', 'HRD', 'Aktif'],
    ['Rizky Maulana', 'manager@spk.test', 'Atasan', 'Aktif'],
    ['Arief Wicaksono', 'direktur@spk.test', 'Pimpinan', 'Aktif'],
    ['Siti Rahma', 'karyawan@spk.test', 'Karyawan', 'Nonaktif'],
  ].filter(r => (!filter || r[2] === filter) && r.join(' ').toLowerCase().includes(search.toLowerCase()))
  return <><PageHeader title="Manajemen User & Role" description="Kelola akun pengguna dan lihat role tetap sistem." actions={tab === 'user' ? <Button icon="plus" onClick={() => setModal(true)}>Tambah User</Button> : undefined} /><div className="tabs"><button className={tab === 'user' ? 'active' : ''} onClick={() => setTab('user')}>Daftar User</button><button className={tab === 'role' ? 'active' : ''} onClick={() => setTab('role')}>Manajemen Role</button></div><section className="card card--table">{tab === 'user' ? <><DataToolbar search={search} setSearch={setSearch} filter={filter} setFilter={setFilter} filterOptions={['Admin', 'HRD', 'Atasan', 'Pimpinan', 'Karyawan']} /><div className="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th aria-label="Aksi" /></tr></thead><tbody>{rows.map(r => <tr key={r[1]}><td><div className="person-cell"><span className="avatar">{r[0].split(' ').map(x => x[0]).join('')}</span><strong>{r[0]}</strong></div></td><td>{r[1]}</td><td>{r[2]}</td><td><Badge tone={r[3] === 'Aktif' ? 'success' : 'neutral'}>{r[3]}</Badge></td><td><div className="table-actions"><Button variant="ghost" icon="edit" className="icon-only"><span className="sr-only">Ubah</span></Button></div></td></tr>)}</tbody></table></div><Pagination count={48} /></> : <div className="role-grid">{[['Admin', 'Mengelola user, role, dan data master teknis.'], ['HRD', 'Mengelola proses penilaian dan menjalankan SAW.'], ['Atasan', 'Menilai bawahan langsung dan melihat hasil tim.'], ['Pimpinan', 'Meninjau ranking dan memutuskan rekomendasi.'], ['Karyawan', 'Opsional — melihat nilai milik sendiri.']].map(x => <article key={x[0]}><span className="stat-card__icon"><Icon name="lock" /></span><h3>{x[0]}</h3><p>{x[1]}</p><Badge tone={x[0] === 'Karyawan' ? 'warning' : 'info'}>{x[0] === 'Karyawan' ? 'Opsional' : 'Role tetap'}</Badge></article>)}</div>}</section>{modal && <Modal title="Tambah User" description="Setiap user hanya dapat memiliki satu role." onClose={() => setModal(false)}><form onSubmit={(e) => { e.preventDefault(); setModal(false); notify('User baru berhasil ditambahkan.') }}><div className="form-grid"><Field label="Nama lengkap"><input required placeholder="Nama pengguna" /></Field><Field label="Email"><input required type="email" placeholder="nama@perusahaan.co.id" /></Field><Field label="Password" hint="Minimal 8 karakter, berisi huruf dan angka."><input required minLength={8} type="password" /></Field><Field label="Role"><select required defaultValue=""><option value="" disabled>Pilih role</option>{['Admin', 'HRD', 'Atasan', 'Pimpinan', 'Karyawan'].map(x => <option key={x}>{x}</option>)}</select></Field></div><div className="modal__actions"><Button variant="secondary" onClick={() => setModal(false)}>Batal</Button><Button type="submit">Simpan User</Button></div></form></Modal>}</>
}

function EmployeesPage({ notify }: { notify: (msg: string) => void }) {
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('')
  const [modal, setModal] = useState(false)
  const rows = employees.filter(x => (!filter || x.dept === filter) && Object.values(x).join(' ').toLowerCase().includes(search.toLowerCase()))
  return <><PageHeader title="Data Karyawan" description="Kelola data karyawan yang menjadi alternatif dalam proses penilaian." actions={<div className="button-row"><Button variant="secondary" icon="download" onClick={() => notify('Impor merupakan fitur prioritas sedang dan disimulasikan pada prototipe.')}>Impor Data</Button><Button icon="plus" onClick={() => setModal(true)}>Tambah Karyawan</Button></div>} /><section className="card card--table"><DataToolbar search={search} setSearch={setSearch} filter={filter} setFilter={setFilter} filterOptions={['Teknologi Informasi', 'Operasional', 'Keuangan']} /><div className="table-wrap"><table><thead><tr><th>Karyawan</th><th>Departemen</th><th>Jabatan</th><th>Atasan Langsung</th><th>Tanggal Masuk</th><th aria-label="Aksi" /></tr></thead><tbody>{rows.map(r => <tr key={r.nik}><td><div className="person-cell"><span className="avatar">{r.name.split(' ').map(x => x[0]).join('')}</span><span><strong>{r.name}</strong><small>{r.nik}</small></span></div></td><td>{r.dept}</td><td>{r.position}</td><td>{r.supervisor}</td><td>{r.joined}</td><td><Button variant="ghost" icon="edit" className="icon-only"><span className="sr-only">Ubah</span></Button></td></tr>)}</tbody></table></div><Pagination /></section>{modal && <Modal title="Tambah Karyawan" description="NIK harus unik dan seluruh data organisasi wajib diisi." onClose={() => setModal(false)} width="wide"><form onSubmit={(e) => { e.preventDefault(); setModal(false); notify('Data karyawan berhasil ditambahkan.') }}><div className="form-grid form-grid--3"><Field label="NIK"><input required placeholder="KRY-0000" /></Field><Field label="Nama lengkap"><input required /></Field><Field label="Tanggal masuk"><input required type="date" /></Field><Field label="Departemen"><select required defaultValue=""><option value="" disabled>Pilih departemen</option><option>Teknologi Informasi</option><option>Operasional</option><option>Keuangan</option></select></Field><Field label="Jabatan"><select required defaultValue=""><option value="" disabled>Pilih jabatan</option><option>Staf</option><option>Staf Senior</option><option>Koordinator</option></select></Field><Field label="Level jabatan"><input required type="number" min="1" /></Field><Field label="Atasan langsung"><select required defaultValue=""><option value="" disabled>Pilih atasan</option><option>Rizky Maulana</option><option>Budi Santoso</option><option>Maya Putri</option></select></Field><Field label="Akun user (opsional)"><select><option>Tidak ditautkan</option><option>karyawan@spk.test</option></select></Field></div><div className="modal__actions"><Button variant="secondary" onClick={() => setModal(false)}>Batal</Button><Button type="submit">Simpan Karyawan</Button></div></form></Modal>}</>
}

function OrganizationPage({ notify }: { notify: (msg: string) => void }) {
  const [tab, setTab] = useState<'dept' | 'position'>('dept')
  return <><PageHeader title="Departemen & Jabatan" description="Kelola struktur organisasi dan level jabatan." actions={<Button icon="plus" onClick={() => notify(`${tab === 'dept' ? 'Departemen' : 'Jabatan'} baru berhasil ditambahkan.`)}>Tambah {tab === 'dept' ? 'Departemen' : 'Jabatan'}</Button>} /><div className="tabs"><button className={tab === 'dept' ? 'active' : ''} onClick={() => setTab('dept')}>Departemen</button><button className={tab === 'position' ? 'active' : ''} onClick={() => setTab('position')}>Jabatan / Posisi</button></div><section className="card card--table"><div className="table-wrap"><table><thead><tr><th>{tab === 'dept' ? 'Nama Departemen' : 'Nama Jabatan'}</th>{tab === 'position' && <th>Level</th>}<th>Jumlah Karyawan</th><th aria-label="Aksi" /></tr></thead><tbody>{(tab === 'dept' ? [['Teknologi Informasi', '18'], ['Operasional', '21'], ['Keuangan', '11']] : [['Staf', '1', '20'], ['Staf Senior', '2', '14'], ['Koordinator', '3', '9'], ['Manajer', '4', '5']]).map(r => <tr key={r[0]}><td><strong>{r[0]}</strong></td>{tab === 'position' && <td>Level {r[1]}</td>}<td>{r[r.length - 1]} karyawan</td><td><div className="table-actions"><Button variant="ghost" icon="edit" className="icon-only"><span className="sr-only">Ubah</span></Button><Button variant="ghost" onClick={() => notify('Data yang masih dipakai karyawan tidak dapat dihapus.')}>Hapus</Button></div></td></tr>)}</tbody></table></div></section></>
}

function PeriodsPage({ notify }: { notify: (msg: string) => void }) {
  const [confirm, setConfirm] = useState(false)
  return <><PageHeader title="Periode Penilaian" description="Kelola siklus penilaian dan akses data historis." actions={<Button icon="plus" onClick={() => notify('Periode baru disimpan sebagai draf.')}>Tambah Periode</Button>} /><section className="card card--table"><div className="table-wrap"><table><thead><tr><th>Nama Periode</th><th>Rentang Tanggal</th><th>Status</th><th>Bobot</th><th>Assessment</th><th aria-label="Aksi" /></tr></thead><tbody><tr><td><strong>Semester 1 2026</strong><small className="table-subtitle">Periode aktif</small></td><td>01-01-2026 — 30-06-2026</td><td><Badge tone="info">Berjalan</Badge></td><td><Badge tone="success">100%</Badge></td><td>38 / 50</td><td><Button variant="secondary" onClick={() => setConfirm(true)}>Tutup Periode</Button></td></tr><tr><td><strong>Semester 2 2025</strong></td><td>01-07-2025 — 31-12-2025</td><td><Badge tone="neutral">Ditutup</Badge></td><td>100%</td><td>47 / 47</td><td><Button variant="ghost" icon="eye">Lihat</Button></td></tr><tr><td><strong>Semester 2 2026</strong></td><td>01-07-2026 — 31-12-2026</td><td><Badge tone="warning">Draf</Badge></td><td>80%</td><td>—</td><td><Button variant="ghost" icon="edit">Ubah</Button></td></tr></tbody></table></div></section><div className="info-panel"><Icon name="alert" /><div><strong>Ketentuan periode</strong><p>Hanya satu periode dapat berstatus berjalan. Periode hanya dapat dibuka jika total bobot tepat 100%, dan periode dengan assessment tidak dapat dihapus.</p></div></div>{confirm && <Modal title="Tutup periode penilaian?" description="Seluruh assessment akan dikunci dan tidak dapat diubah." onClose={() => setConfirm(false)}><div className="alert alert--warning"><Icon name="alert" /><span>Masih ada 12 assessment yang belum dikirim.</span></div><div className="modal__actions"><Button variant="secondary" onClick={() => setConfirm(false)}>Batal</Button><Button variant="danger" onClick={() => { setConfirm(false); notify('Periode ditutup dan seluruh assessment dikunci.') }}>Tutup Periode</Button></div></Modal>}</>
}

function CriteriaPage({ notify }: { notify: (msg: string) => void }) {
  const [weights, setWeights] = useState(criteria.map(x => x.weight))
  const total = weights.reduce((a, b) => a + b, 0)
  return <><PageHeader title="Kriteria & Bobot" description="Atur kriteria dan bobot untuk Semester 1 2026." actions={<div className="button-row"><Button variant="secondary" onClick={() => { setWeights(criteria.map(x => x.weight)); notify('Bobot periode sebelumnya berhasil disalin.') }}>Salin dari Periode Lalu</Button><Button icon="plus" onClick={() => notify('Form kriteria baru dibuka.')}>Tambah Kriteria</Button></div>} /><div className={`weight-summary ${total === 100 ? 'weight-summary--valid' : 'weight-summary--invalid'}`}><div><span>Total Bobot</span><strong>{total}%</strong><small>{total === 100 ? 'Bobot valid dan siap digunakan' : 'Total bobot harus tepat 100%'}</small></div><div className="weight-ring" style={{ '--progress': `${Math.min(total, 100) * 3.6}deg` } as React.CSSProperties}><span>{total}</span></div></div><section className="card card--table"><div className="table-wrap"><table><thead><tr><th>Kode</th><th>Kriteria</th><th>Tipe Atribut</th><th>Bobot</th><th aria-label="Aksi" /></tr></thead><tbody>{criteria.map((c, i) => <tr key={c.code}><td><span className="code-chip">{c.code}</span></td><td><strong>{c.name}</strong><small className="table-subtitle">{c.desc}</small></td><td><Badge tone="info">{c.type}</Badge></td><td><div className="weight-input"><input type="number" min="0" max="100" value={weights[i]} onChange={e => setWeights(weights.map((w, x) => x === i ? Number(e.target.value) : w))} /><span>%</span></div></td><td><Button variant="ghost" icon="edit" className="icon-only"><span className="sr-only">Ubah</span></Button></td></tr>)}</tbody></table></div><div className="card__footer"><span>Perubahan bobot akan dikunci setelah perhitungan SAW dijalankan.</span><Button disabled={total !== 100} onClick={() => notify('Bobot kriteria berhasil disimpan.')}>Simpan Bobot</Button></div></section></>
}

function EmployeeAssessmentTable({ compact = false, onAssess }: { compact?: boolean; onAssess: (name: string) => void }) {
  const rows = [
    ['Siti Rahma', 'System Analyst', 'Sudah Dinilai', '89,6'],
    ['Rian Pratama', 'Software Engineer', 'Draf', '78,5'],
    ['Bagas Saputra', 'UI Designer', 'Belum Dinilai', '—'],
    ['Nina Kurnia', 'QA Engineer', 'Belum Dinilai', '—'],
  ].slice(0, compact ? 3 : 4)
  return <div className="table-wrap"><table><thead><tr><th>Karyawan</th><th>Status</th><th>Rata-rata Sementara</th><th aria-label="Aksi" /></tr></thead><tbody>{rows.map(r => <tr key={r[0]}><td><div className="person-cell"><span className="avatar">{r[0].split(' ').map(x => x[0]).join('')}</span><span><strong>{r[0]}</strong><small>{r[1]}</small></span></div></td><td><Badge tone={r[2] === 'Sudah Dinilai' ? 'success' : r[2] === 'Draf' ? 'warning' : 'neutral'}>{r[2]}</Badge></td><td>{r[3]}</td><td><Button variant={r[2] === 'Sudah Dinilai' ? 'ghost' : 'secondary'} onClick={() => onAssess(r[0])}>{r[2] === 'Sudah Dinilai' ? 'Lihat' : r[2] === 'Draf' ? 'Lanjutkan' : 'Input Nilai'}</Button></td></tr>)}</tbody></table></div>
}

function AssessmentPage({ notify }: { notify: (msg: string) => void }) {
  const [person, setPerson] = useState<string | null>(null)
  const [scores, setScores] = useState<(string)[]>(['', '', '', '', ''])
  const [errors, setErrors] = useState(false)
  const submit = () => {
    if (scores.some(x => x === '' || Number(x) < 0 || Number(x) > 100)) return setErrors(true)
    setPerson(null); setErrors(false); notify('Nilai berhasil dikirim dan status diperbarui.')
  }
  return <><PageHeader title="Penilaian Bawahan" description="Input nilai 0–100 hanya untuk bawahan langsung pada periode aktif." /><div className="deadline-banner deadline-banner--compact"><span><Icon name="calendar" /></span><div><strong>Semester 1 2026</strong><p>Periode berjalan hingga 30-06-2026</p></div><Badge tone="info">5 dari 8 selesai</Badge></div><section className="card card--table"><DataToolbar search="" setSearch={() => {}} filter="" setFilter={() => {}} filterOptions={['Belum Dinilai', 'Draf', 'Sudah Dinilai']} /><EmployeeAssessmentTable onAssess={setPerson} /><Pagination count={8} /></section>{person && <Modal title={`Penilaian · ${person}`} description="Nilai setiap kriteria berada pada rentang 0–100." onClose={() => setPerson(null)} width="wide"><div className="assessment-person"><span className="avatar avatar--large">{person.split(' ').map(x => x[0]).join('')}</span><span><strong>{person}</strong><small>Teknologi Informasi · Semester 1 2026</small></span><Badge tone="info">Bobot hanya-baca</Badge></div><div className="criteria-form">{criteria.map((c, i) => <div key={c.code} className="criteria-form__row"><span className="code-chip">{c.code}</span><span><strong>{c.name}</strong><small>{c.desc}</small></span><Badge tone="neutral">Bobot {c.weight}%</Badge><Field label="Nilai" error={errors && (scores[i] === '' || Number(scores[i]) < 0 || Number(scores[i]) > 100) ? 'Wajib diisi (0–100).' : undefined}><input type="number" min="0" max="100" value={scores[i]} onChange={e => { setScores(scores.map((x, n) => n === i ? e.target.value : x)); setErrors(false) }} /></Field></div>)}</div><div className="modal__actions modal__actions--between"><span className="autosave"><span /> Draf disimpan otomatis</span><div className="button-row"><Button variant="secondary" onClick={() => { setPerson(null); notify('Penilaian disimpan sebagai draf.') }}>Simpan Draf</Button><Button onClick={submit}>Simpan & Kirim Nilai</Button></div></div></Modal>}</>
}

function MonitorPage({ notify }: { notify: (msg: string) => void }) {
  return <><PageHeader title="Monitor Status Assessment" description="Pantau kelengkapan penilaian dan kirim pengingat kepada atasan." actions={<Button icon="mail" onClick={() => notify('Pengingat massal berhasil dikirim kepada atasan terkait.')}>Kirim Pengingat Massal</Button>} /><div className="stats-grid stats-grid--3"><StatCard label="Submitted" value="38" note="76% dari total assessment" icon="check" tone="teal" /><StatCard label="Draf" value="7" note="Sudah mulai diisi" icon="edit" tone="amber" /><StatCard label="Belum Diisi" value="5" note="Perlu ditindaklanjuti" icon="clipboard" tone="violet" /></div><section className="card card--table"><DataToolbar search="" setSearch={() => {}} filter="" setFilter={() => {}} filterOptions={['Belum Diisi', 'Draf', 'Submitted']} /><div className="table-wrap"><table><thead><tr><th>Karyawan</th><th>Atasan</th><th>Departemen</th><th>Status</th><th>Terakhir Diperbarui</th><th aria-label="Aksi" /></tr></thead><tbody>{[['Siti Rahma', 'Rizky Maulana', 'Teknologi Informasi', 'Submitted', '18-06-2026 09:42'], ['Rian Pratama', 'Rizky Maulana', 'Teknologi Informasi', 'Draf', '18-06-2026 08:15'], ['Bagas Saputra', 'Rizky Maulana', 'Teknologi Informasi', 'Belum Diisi', '—'], ['Ahmad Fauzi', 'Budi Santoso', 'Operasional', 'Submitted', '17-06-2026 15:21']].map(r => <tr key={r[0]}><td><strong>{r[0]}</strong></td><td>{r[1]}</td><td>{r[2]}</td><td><Badge tone={r[3] === 'Submitted' ? 'success' : r[3] === 'Draf' ? 'warning' : 'neutral'}>{r[3]}</Badge></td><td>{r[4]}</td><td>{r[3] !== 'Submitted' && <Button variant="ghost" icon="mail" onClick={() => notify(`Pengingat untuk ${r[0]} berhasil dikirim.`)}>Ingatkan</Button>}</td></tr>)}</tbody></table></div><Pagination /></section></>
}

function CalculationPage({ notify, goTo }: { notify: (msg: string) => void; goTo: (page: Page) => void }) {
  const [tab, setTab] = useState<'X' | 'R' | 'W' | 'V'>('X')
  const [confirm, setConfirm] = useState(false)
  const [calculated, setCalculated] = useState(true)
  const matrix = tab === 'X' ? [['85', '92', '88', '90', '85'], ['78', '94', '90', '86', '90'], ['95', '82', '85', '93', '78']] : tab === 'R' ? rankingData.slice(0, 3).map(x => x.values) : []
  return <><PageHeader title="Proses Perhitungan SAW" description="Validasi data, jalankan perhitungan, dan telusuri matriks X, R, W, dan V." actions={<Button icon="calculator" onClick={() => setConfirm(true)}>Jalankan Perhitungan SAW</Button>} /><div className="readiness-grid"><div className="readiness-card"><span><Icon name="sliders" /></span><div><strong>Bobot Kriteria</strong><p>Total bobot tepat 100%</p></div><Badge tone="success">Valid</Badge></div><div className="readiness-card"><span><Icon name="clipboard" /></span><div><strong>Kelengkapan Assessment</strong><p>38 submitted, 12 belum lengkap</p></div><Badge tone="warning">Peringatan</Badge></div><div className="readiness-card"><span><Icon name="calendar" /></span><div><strong>Periode</strong><p>Semester 1 2026 sedang berjalan</p></div><Badge tone="info">Aktif</Badge></div></div><section className="card calculation-card"><div className="card__header"><div><h2>Detail Perhitungan</h2><p>Hasil tersimpan terakhir: 18-06-2026 10:15 WIB</p></div><div className="button-row"><Button variant="secondary" icon="download" onClick={() => notify('Matriks CSV berhasil disiapkan.')}>Ekspor CSV</Button><Badge tone={calculated ? 'success' : 'warning'}>{calculated ? 'Tersimpan' : 'Belum dihitung'}</Badge></div></div><div className="tabs tabs--compact">{(['X', 'R', 'W', 'V'] as const).map(x => <button key={x} className={tab === x ? 'active' : ''} onClick={() => setTab(x)}>Matriks {x}</button>)}</div>{tab === 'W' ? <div className="weights-visual">{criteria.map(x => <div key={x.code}><div><strong>{x.code} · {x.name}</strong><span>{x.weight}%</span></div><Progress value={x.weight * 3} /></div>)}</div> : tab === 'V' ? <div className="formula-result"><div><span>Rumus nilai preferensi</span><strong>Vᵢ = Σ wⱼ · rᵢⱼ</strong><p>Skor dihitung dengan presisi 4 desimal dan diurutkan dari nilai terbesar.</p></div><Button onClick={() => goTo('ranking')}>Lihat Hasil Ranking</Button></div> : <div className="table-wrap"><table className="matrix-table"><thead><tr><th>Alternatif</th><th>Karyawan</th>{criteria.map(x => <th key={x.code}>{x.code}</th>)}</tr></thead><tbody>{matrix.map((r, i) => <tr key={i}><td>A{i + 1}</td><td><strong>{rankingData[i].name}</strong></td>{r.map((x, n) => <td key={n}>{x}</td>)}</tr>)}</tbody></table></div>}</section>{confirm && <Modal title="Jalankan perhitungan SAW?" description="Sistem akan menghitung ulang dan menimpa hasil sebelumnya." onClose={() => setConfirm(false)}><div className="alert alert--warning"><Icon name="alert" /><span>12 assessment belum berstatus submitted dan tidak akan masuk ke matriks keputusan.</span></div><p className="modal-copy">Perhitungan ulang hanya diperbolehkan sebelum ada keputusan Pimpinan.</p><div className="modal__actions"><Button variant="secondary" onClick={() => setConfirm(false)}>Batal</Button><Button onClick={() => { setConfirm(false); setCalculated(true); notify('Perhitungan SAW selesai. Ranking berhasil diperbarui.') }}>Tetap Hitung</Button></div></Modal>}</>
}

function RankingPage({ role, notify }: { role: Role; notify: (msg: string) => void }) {
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('')
  const [detail, setDetail] = useState<typeof rankingData[0] | null>(null)
  const data = rankingData.filter(x => (!filter || x.status === filter) && x.name.toLowerCase().includes(search.toLowerCase())).filter((_, i) => role !== 'Atasan' || i < 3)
  return <><PageHeader title={role === 'Atasan' ? 'Hasil Rekomendasi Bawahan' : 'Hasil & Ranking'} description={role === 'Atasan' ? 'Ranking global untuk bawahan langsung Anda.' : 'Hasil perhitungan SAW dan rincian nilai ternormalisasi.'} actions={(role === 'HRD' || role === 'Atasan') ? <Button icon="download" onClick={() => notify(role === 'HRD' ? 'Laporan PDF/XLS berhasil disiapkan.' : 'Berkas rekomendasi berhasil disiapkan.')}>{role === 'HRD' ? 'Ekspor Laporan' : 'Unduh Rekomendasi'}</Button> : undefined} /><div className="stats-grid stats-grid--3"><StatCard label="Total Kandidat" value={role === 'Atasan' ? '8' : '50'} note={role === 'Atasan' ? 'Bawahan langsung' : 'Semester 1 2026'} icon="users" /><StatCard label="Ambang Rekomendasi" value="≥ 0,900" note="Khusus peringkat 1–3" icon="ranking" tone="teal" /><StatCard label={role === 'Atasan' ? 'Rata-rata Tim' : 'Rata-rata Skor'} value="0,9137" note="Skor preferensi SAW" icon="calculator" tone="violet" /></div><section className="card card--table"><DataToolbar search={search} setSearch={setSearch} filter={filter} setFilter={setFilter} filterOptions={['Direkomendasikan', 'Memenuhi Syarat', 'Dipertimbangkan']} /><div className="table-wrap"><table className="ranking-table"><thead><tr><th>Rank</th><th>Karyawan</th>{criteria.map(x => <th key={x.code}>{x.code}</th>)}<th>Skor Vᵢ</th><th>Status</th><th aria-label="Aksi" /></tr></thead><tbody>{data.map(r => <tr key={r.name}><td><span className={`rank-badge ${r.rank <= 3 ? 'rank-badge--top' : ''}`}>{r.rank}</span></td><td><div className="person-cell"><span className="avatar">{r.name.split(' ').map(x => x[0]).join('')}</span><span><strong>{r.name}</strong><small>{r.dept}</small></span></div></td>{r.values.map((x, i) => <td key={i}>{x}</td>)}<td><strong className="score">{r.score}</strong></td><td><Badge tone={r.status === 'Direkomendasikan' ? 'success' : 'info'}>{r.status}</Badge></td><td><Button variant="ghost" icon="eye" onClick={() => setDetail(r)}>Detail</Button></td></tr>)}</tbody></table></div><Pagination /></section><div className="formula-strip"><span>Transparansi perhitungan</span><strong>Vᵢ = Σ (wⱼ × rᵢⱼ)</strong><p>Klik detail kandidat untuk melihat kontribusi setiap kriteria.</p></div>{detail && <CandidateDetail candidate={detail} onClose={() => setDetail(null)} />}</>
}

function CandidateDetail({ candidate, onClose, approvalActions }: { candidate: typeof rankingData[0]; onClose: () => void; approvalActions?: ReactNode }) {
  return <Modal title="Detail Kandidat" description="Jejak perhitungan dan ringkasan kandidat." onClose={onClose} width="wide"><div className="candidate-summary"><span className="avatar avatar--xl">{candidate.name.split(' ').map(x => x[0]).join('')}</span><div><h3>{candidate.name}</h3><p>{candidate.nik} · {candidate.dept}</p><Badge tone="success">{candidate.status}</Badge></div><div className="candidate-summary__score"><span>Skor akhir</span><strong>{candidate.score}</strong><small>Peringkat #{candidate.rank}</small></div></div><div className="detail-grid"><section><h4>Kontribusi Kriteria</h4><div className="contribution-list">{criteria.map((c, i) => { const value = Number(candidate.values[i].replace(',', '.')) * c.weight / 100; return <div key={c.code}><div><span><strong>{c.code}</strong> {c.name}</span><span>{c.weight}% × {candidate.values[i]} = <strong>{value.toFixed(4).replace('.', ',')}</strong></span></div><Progress value={Number(candidate.values[i].replace(',', '.')) * 100} tone="teal" /></div> })}</div></section><section><h4>Informasi Kandidat</h4><dl className="detail-list"><div><dt>Jabatan saat ini</dt><dd>System Analyst</dd></div><div><dt>Usulan jabatan</dt><dd>Koordinator TI</dd></div><div><dt>Masa kerja</dt><dd>6 tahun 10 bulan</dd></div><div><dt>Penilai</dt><dd>Rizky Maulana</dd></div></dl></section></div>{approvalActions || <div className="modal__actions"><Button variant="secondary" onClick={onClose}>Tutup</Button></div>}</Modal>
}

function RecommendationsPage({ notify }: { notify: (msg: string) => void }) {
  const [detail, setDetail] = useState<typeof rankingData[0] | null>(null)
  const [decision, setDecision] = useState<'approve' | 'reject' | 'review' | null>(null)
  const [note, setNote] = useState('')
  const [error, setError] = useState(false)
  const finish = () => {
    if (decision === 'reject' && !note.trim()) return setError(true)
    const label = decision === 'approve' ? 'disetujui' : decision === 'reject' ? 'ditolak' : 'dikirim untuk review ulang'
    setDecision(null); setDetail(null); setNote(''); setError(false); notify(`Rekomendasi berhasil ${label}.`)
  }
  return <><PageHeader title="Daftar Rekomendasi Promosi" description="Tinjau kandidat yang diajukan sistem dan berikan keputusan akhir." /><section className="card card--table"><DataToolbar search="" setSearch={() => {}} filter="" setFilter={() => {}} filterOptions={['Menunggu', 'Disetujui', 'Ditolak', 'Review']} /><div className="table-wrap"><table><thead><tr><th>Kandidat</th><th>Rank</th><th>Skor</th><th>Usulan Jabatan</th><th>Status Persetujuan</th><th aria-label="Aksi" /></tr></thead><tbody>{rankingData.slice(0, 3).map((r, i) => <tr key={r.name}><td><div className="person-cell"><span className="avatar">{r.name.split(' ').map(x => x[0]).join('')}</span><span><strong>{r.name}</strong><small>{r.dept}</small></span></div></td><td>#{r.rank}</td><td><strong className="score">{r.score}</strong></td><td>{['Koordinator TI', 'Supervisor Operasional', 'Lead Engineer'][i]}</td><td><Badge tone="warning">Menunggu</Badge></td><td><Button onClick={() => setDetail(r)}>Tinjau</Button></td></tr>)}</tbody></table></div></section>{detail && !decision && <CandidateDetail candidate={detail} onClose={() => setDetail(null)} approvalActions={<div className="approval-actions"><Button variant="danger" onClick={() => setDecision('reject')}>Tolak Rekomendasi</Button><Button variant="secondary" onClick={() => setDecision('review')}>Minta Review Ulang</Button><Button onClick={() => setDecision('approve')}>Setujui Promosi</Button></div>} />}{decision && <Modal title={decision === 'approve' ? 'Setujui promosi?' : decision === 'reject' ? 'Tolak rekomendasi?' : 'Minta review ulang?'} description={detail ? `Keputusan untuk ${detail.name}` : ''} onClose={() => setDecision(null)}><div className={`alert ${decision === 'approve' ? 'alert--success' : 'alert--warning'}`}><Icon name={decision === 'approve' ? 'check' : 'alert'} /><span>{decision === 'approve' ? 'Keputusan ini final untuk periode berjalan.' : decision === 'reject' ? 'Alasan penolakan wajib diisi.' : 'Kandidat akan dikembalikan kepada HRD untuk ditinjau.'}</span></div>{decision !== 'approve' && <Field label={decision === 'reject' ? 'Alasan penolakan' : 'Catatan review'} error={error ? 'Alasan penolakan wajib diisi.' : undefined}><textarea rows={4} value={note} onChange={e => { setNote(e.target.value); setError(false) }} placeholder="Tuliskan catatan keputusan..." /></Field>}<div className="modal__actions"><Button variant="secondary" onClick={() => setDecision(null)}>Batal</Button><Button variant={decision === 'reject' ? 'danger' : 'primary'} onClick={finish}>Konfirmasi Keputusan</Button></div></Modal>}</>
}

function MyScorePage() {
  return <><PageHeader title="Nilai Saya" description="Hasil penilaian pribadi setelah periode ditutup." /><div className="optional-banner"><Icon name="lock" /><span><strong>Data bersifat pribadi</strong><small>Anda hanya dapat melihat data penilaian milik sendiri.</small></span></div><div className="score-hero"><div><span>Skor preferensi Semester 2 2025</span><strong>0,9619</strong><Badge tone="success">Direkomendasikan</Badge></div><div><span>Peringkat global</span><strong>#1</strong><small>dari 47 karyawan</small></div></div><section className="card"><div className="card__header"><div><h2>Rincian Nilai per Kriteria</h2><p>Nilai mentah, bobot, dan nilai ternormalisasi</p></div></div><div className="table-wrap"><table><thead><tr><th>Kriteria</th><th>Nilai Mentah</th><th>Bobot</th><th>Nilai Ternormalisasi</th></tr></thead><tbody>{criteria.map((c, i) => <tr key={c.code}><td><strong>{c.code} · {c.name}</strong></td><td>{[78, 94, 90, 86, 90][i]}</td><td>{c.weight}%</td><td>{rankingData[0].values[i]}</td></tr>)}</tbody></table></div></section><section className="card"><div className="card__header"><div><h2>Riwayat Penilaian</h2><p>Hasil periode sebelumnya</p></div></div><div className="history-row"><span>Semester 1 2025</span><strong>0,9245</strong><span>Peringkat #4</span><Badge tone="info">Memenuhi Syarat</Badge></div></section></>
}

function AppShell({ role, onLogout }: { role: Role; onLogout: () => void }) {
  const [page, setPage] = useState<Page>('dashboard')
  const [sidebar, setSidebar] = useState(false)
  const [notifications, setNotifications] = useState(false)
  const [profile, setProfile] = useState(false)
  const [passwordModal, setPasswordModal] = useState(false)
  const [toast, setToast] = useState('')
  const user = roleNames[role]
  const notify = (msg: string) => { setToast(msg); window.setTimeout(() => setToast(''), 3500) }
  const title = navByRole[role].find(x => x.page === page)?.label || 'Dashboard'
  const pageContent = useMemo(() => {
    if (page === 'dashboard') return <Dashboard role={role} goTo={setPage} />
    if (page === 'users') return <UsersPage notify={notify} />
    if (page === 'employees') return <EmployeesPage notify={notify} />
    if (page === 'organization') return <OrganizationPage notify={notify} />
    if (page === 'periods') return <PeriodsPage notify={notify} />
    if (page === 'criteria') return <CriteriaPage notify={notify} />
    if (page === 'monitor') return <MonitorPage notify={notify} />
    if (page === 'calculation') return <CalculationPage notify={notify} goTo={setPage} />
    if (page === 'assessments') return <AssessmentPage notify={notify} />
    if (page === 'recommendations') return <RecommendationsPage notify={notify} />
    if (page === 'my-score') return <MyScorePage />
    return <RankingPage role={role} notify={notify} />
  }, [page, role])
  return <div className="app-shell">
    {sidebar && <div className="sidebar-overlay" onClick={() => setSidebar(false)} />}
    <aside className={`sidebar ${sidebar ? 'sidebar--open' : ''}`}>
      <div className="brand"><span className="brand__mark"><Icon name="ranking" size={22} /></span><span><strong>SPK</strong> Promosi</span></div>
      <nav><span className="nav-label">MENU UTAMA</span>{navByRole[role].map(item => <button key={item.label} className={page === item.page ? 'active' : ''} onClick={() => { setPage(item.page); setSidebar(false) }}><Icon name={item.icon} /><span>{item.label}</span></button>)}</nav>
      <div className="sidebar__context"><span>Periode aktif</span><strong>Semester 1 2026</strong><small>01 Jan – 30 Jun 2026</small></div>
      <div className="sidebar__footer"><span className="sidebar__footer-icon"><Icon name="calculator" /></span><span><strong>Metode SAW</strong><small>Simple Additive Weighting</small></span></div>
    </aside>
    <div className="app-main">
      <header className="topbar">
        <button className="mobile-menu" onClick={() => setSidebar(true)} aria-label="Buka menu"><Icon name="menu" /></button>
        <div className="topbar__period"><span>Periode</span><select aria-label="Pilih periode" defaultValue="s1-2026"><option value="s1-2026">Semester 1 2026</option><option value="s2-2025">Semester 2 2025</option></select></div>
        <div className="topbar__actions">
          <div className="popover-wrap"><button className="topbar-icon" onClick={() => { setNotifications(!notifications); setProfile(false) }} aria-label="Notifikasi"><Icon name="bell" /><span /></button>{notifications && <div className="popover notification-popover"><div className="popover__header"><strong>Notifikasi</strong><Badge tone="info">3 baru</Badge></div><div className="notification-item"><span className="notification-item__icon"><Icon name="clipboard" /></span><span><strong>Assessment diperbarui</strong><small>Siti Rahma telah selesai dinilai.</small><time>10 menit lalu</time></span></div><div className="notification-item"><span className="notification-item__icon"><Icon name="calendar" /></span><span><strong>Periode segera berakhir</strong><small>Tersisa 12 hari hingga penutupan.</small><time>1 jam lalu</time></span></div></div>}</div>
          <div className="popover-wrap"><button className="profile-trigger" onClick={() => { setProfile(!profile); setNotifications(false) }}><span className="avatar">{user.name.split(' ').map(x => x[0]).join('')}</span><span><strong>{user.name}</strong><small>{role}{role === 'Karyawan' ? ' · Opsional' : ''}</small></span><Icon name="chevron" size={14} /></button>{profile && <div className="popover profile-popover"><div><strong>{user.name}</strong><small>{user.email}</small></div><button onClick={() => { setPasswordModal(true); setProfile(false) }}><Icon name="lock" /> Ubah Password</button><button onClick={onLogout}><Icon name="logout" /> Keluar</button></div>}</div>
        </div>
      </header>
      <main className="content" key={`${role}-${page}`}>{pageContent}</main>
    </div>
    {toast && <div className="toast"><span><Icon name="check" /></span>{toast}<button onClick={() => setToast('')} aria-label="Tutup notifikasi"><Icon name="close" size={16} /></button></div>}
    {passwordModal && <Modal title="Ubah Password" description="Gunakan password yang kuat untuk menjaga keamanan akun." onClose={() => setPasswordModal(false)}><form onSubmit={(e) => { e.preventDefault(); setPasswordModal(false); notify('Password berhasil diubah.') }}><div className="stack"><Field label="Password saat ini"><input required type="password" /></Field><Field label="Password baru" hint="Minimal 8 karakter, berisi huruf dan angka."><input required minLength={8} type="password" /></Field><Field label="Konfirmasi password baru"><input required minLength={8} type="password" /></Field></div><div className="modal__actions"><Button variant="secondary" onClick={() => setPasswordModal(false)}>Batal</Button><Button type="submit">Simpan Password</Button></div></form></Modal>}
    <span className="sr-only">Halaman saat ini: {title}</span>
  </div>
}

export default function App() {
  const [role, setRole] = useState<Role | null>(null)
  return role ? <AppShell role={role} onLogout={() => setRole(null)} /> : <Login onLogin={setRole} />
}
