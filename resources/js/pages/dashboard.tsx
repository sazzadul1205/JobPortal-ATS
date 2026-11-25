import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, Briefcase, Clock, FileText, TrendingUp, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

/* ── Demo data — replace with props from your controller ─────── */
const stats = [
    { label: 'Open jobs', value: 24, delta: +3, trend: 'up' as const, icon: Briefcase },
    { label: 'Applications', value: 312, delta: +18, trend: 'up' as const, icon: FileText },
    { label: 'Candidates', value: 158, delta: -4, trend: 'down' as const, icon: Users },
    { label: 'Hire rate', value: '6.4%', delta: +0.8, trend: 'up' as const, icon: TrendingUp },
];

type AppStatus = 'pending' | 'reviewed' | 'shortlisted' | 'rejected' | 'hired';

const recentApplications: {
    id: number;
    name: string;
    role: string;
    ats: number;
    status: AppStatus;
    date: string;
}[] = [
    { id: 1, name: 'Amina Rahman', role: 'Frontend Engineer', ats: 92, status: 'shortlisted', date: '2h ago' },
    { id: 2, name: 'Marcus Lee', role: 'Backend Engineer', ats: 74, status: 'reviewed', date: '5h ago' },
    { id: 3, name: 'Sofia Alvarez', role: 'Product Designer', ats: 58, status: 'pending', date: '1d ago' },
    { id: 4, name: 'Jonas Weber', role: 'DevOps Engineer', ats: 35, status: 'rejected', date: '1d ago' },
    { id: 5, name: 'Priya Nair', role: 'Data Analyst', ats: 88, status: 'hired', date: '2d ago' },
];

const statusStyles: Record<AppStatus, string> = {
    pending: 'bg-status-pending-tint text-status-pending',
    reviewed: 'bg-status-reviewed-tint text-status-reviewed',
    shortlisted: 'bg-status-shortlisted-tint text-status-shortlisted',
    rejected: 'bg-status-rejected-tint text-status-rejected',
    hired: 'bg-status-hired-tint text-status-hired',
};

function atsColor(score: number) {
    if (score >= 80) return 'text-ats-excellent';
    if (score >= 60) return 'text-ats-good';
    if (score >= 40) return 'text-ats-fair';
    return 'text-ats-poor';
}

function atsLabel(score: number) {
    if (score >= 80) return 'Excellent';
    if (score >= 60) return 'Good';
    if (score >= 40) return 'Fair';
    return 'Poor';
}

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6">
                {/* ── Page header ───────────────────────────────── */}
                <div>
                    <h1 className="text-foreground text-2xl font-bold tracking-tight">Dashboard</h1>
                    <p className="text-muted-foreground mt-1 text-sm">An overview of your hiring pipeline.</p>
                </div>

                {/* ── KPI cards ─────────────────────────────────── */}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map(({ label, value, delta, trend, icon: Icon }) => (
                        <div key={label} className="border-border bg-card rounded-xl border p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-sm font-medium">{label}</span>
                                <span className="bg-primary-tint text-primary grid h-9 w-9 place-items-center rounded-lg">
                                    <Icon className="h-4 w-4" />
                                </span>
                            </div>
                            <div className="mt-3 flex items-end justify-between">
                                <span className="text-foreground text-3xl font-bold tracking-tight">{value}</span>
                                <span
                                    className={`flex items-center gap-1 text-xs font-medium ${
                                        trend === 'up' ? 'text-status-hired' : 'text-status-rejected'
                                    }`}
                                >
                                    {trend === 'up' ? <ArrowUpRight className="h-3.5 w-3.5" /> : <ArrowDownRight className="h-3.5 w-3.5" />}
                                    {Math.abs(delta)}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>

                {/* ── Main grid: table + ATS panel ──────────────── */}
                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Recent applications */}
                    <div className="border-border bg-card rounded-xl border shadow-sm lg:col-span-2">
                        <div className="border-border flex items-center justify-between border-b px-5 py-4">
                            <div>
                                <h2 className="text-foreground text-sm font-semibold">Recent applications</h2>
                                <p className="text-muted-foreground mt-0.5 text-xs">Latest candidates in your pipeline</p>
                            </div>
                            <Link href="/applications" className="text-primary hover:text-primary-hover text-xs font-medium transition-colors">
                                View all
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-border text-muted-foreground border-b text-left text-xs tracking-wide uppercase">
                                        <th className="px-5 py-3 font-medium">Candidate</th>
                                        <th className="px-5 py-3 font-medium">Role</th>
                                        <th className="px-5 py-3 font-medium">ATS</th>
                                        <th className="px-5 py-3 font-medium">Status</th>
                                        <th className="px-5 py-3 font-medium">When</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentApplications.map((app) => (
                                        <tr key={app.id} className="border-border hover:bg-muted/50 border-b last:border-0">
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-2.5">
                                                    <span className="bg-secondary text-secondary-foreground grid h-7 w-7 place-items-center rounded-full text-[11px] font-semibold">
                                                        {app.name
                                                            .split(' ')
                                                            .map((n) => n[0])
                                                            .join('')}
                                                    </span>
                                                    <span className="text-foreground font-medium">{app.name}</span>
                                                </div>
                                            </td>
                                            <td className="text-muted-foreground px-5 py-3">{app.role}</td>
                                            <td className="px-5 py-3">
                                                <span className={`font-semibold ${atsColor(app.ats)}`} title={atsLabel(app.ats)}>
                                                    {app.ats}
                                                </span>
                                            </td>
                                            <td className="px-5 py-3">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusStyles[app.status]}`}
                                                >
                                                    {app.status}
                                                </span>
                                            </td>
                                            <td className="px-5 py-3">
                                                <span className="text-muted-foreground flex items-center gap-1 text-xs">
                                                    <Clock className="h-3 w-3" />
                                                    {app.date}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* ATS breakdown */}
                    <div className="border-border bg-card rounded-xl border p-5 shadow-sm">
                        <h2 className="text-foreground text-sm font-semibold">ATS score tiers</h2>
                        <p className="text-muted-foreground mt-0.5 text-xs">Distribution across active candidates</p>

                        <ul className="mt-5 space-y-4">
                            {[
                                { label: 'Excellent', range: '≥ 80', value: 38, color: 'bg-ats-excellent', text: 'text-ats-excellent' },
                                { label: 'Good', range: '60–79', value: 44, color: 'bg-ats-good', text: 'text-ats-good' },
                                { label: 'Fair', range: '40–59', value: 26, color: 'bg-ats-fair', text: 'text-ats-fair' },
                                { label: 'Poor', range: '< 40', value: 12, color: 'bg-ats-poor', text: 'text-ats-poor' },
                            ].map((tier) => (
                                <li key={tier.label}>
                                    <div className="mb-1.5 flex items-center justify-between text-xs">
                                        <span className="text-foreground flex items-center gap-1.5 font-medium">
                                            <span className={`h-2 w-2 rounded-full ${tier.color}`} />
                                            {tier.label}
                                            <span className="text-muted-foreground font-normal">({tier.range})</span>
                                        </span>
                                        <span className={`font-semibold ${tier.text}`}>{tier.value}</span>
                                    </div>
                                    <div className="bg-muted h-1.5 w-full overflow-hidden rounded-full">
                                        <div className={`h-full rounded-full ${tier.color}`} style={{ width: `${tier.value}%` }} />
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <div className="bg-primary-tint mt-6 rounded-lg p-4">
                            <p className="text-primary text-xs font-medium">Tip</p>
                            <p className="text-primary/80 mt-1 text-xs">Candidates scoring 80+ are 3× more likely to reach the interview stage.</p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
