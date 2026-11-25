import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Stats {
    total_applications: number;
    average_score: number;
    score_distribution: Record<string, number>;
    top_skills: Record<string, number>;
    status_breakdown: Record<string, number>;
}

interface AnalyticsDashboardProps {
    stats: Stats;
    userRole: string;
}

export default function Dashboard({ stats, userRole }: AnalyticsDashboardProps) {
    return (
        <AppLayout>
            <Head title="Analytics Dashboard" />
            <div className="mx-auto max-w-7xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Analytics Dashboard</h1>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-sm text-slate-500">Total Applications</p>
                        <p className="mt-2 text-3xl font-bold text-slate-900">{stats.total_applications}</p>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-sm text-slate-500">Average Score</p>
                        <p className="mt-2 text-3xl font-bold text-slate-900">{stats.average_score}</p>
                    </div>
                </div>
                <div className="mt-8 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="mb-3 text-lg font-semibold text-slate-900">Score Distribution</h2>
                    <ul className="space-y-2">
                        {Object.entries(stats.score_distribution).map(([label, value]) => (
                            <li key={label} className="flex justify-between text-sm text-slate-600">
                                <span>{label}</span>
                                <span className="font-semibold">{value}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </AppLayout>
    );
}
