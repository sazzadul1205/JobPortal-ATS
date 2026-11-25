import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Briefcase, ChevronRight, FileText, LayoutDashboard, Menu, Settings, TrendingUp, Users, X } from 'lucide-react';
import { useState } from 'react';

interface AppLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

interface NavItem {
    label: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
}

const navItemsByRole: Record<string, NavItem[]> = {
    job_seeker: [
        { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { label: 'Browse Jobs', href: '/jobs', icon: Briefcase },
        { label: 'My Applications', href: '/applications', icon: FileText },
        { label: 'Settings', href: '/settings', icon: Settings },
    ],
    employer: [
        { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { label: 'Job Listings', href: '/backend/listing', icon: Briefcase },
        { label: 'Categories', href: '/backend/categories', icon: FileText },
        { label: 'Locations', href: '/backend/locations', icon: Users },
        { label: 'Applications', href: '/backend/application', icon: FileText },
        { label: 'Analytics', href: '/backend/analytics/dashboard', icon: TrendingUp },
        { label: 'Settings', href: '/settings', icon: Settings },
    ],
    admin: [
        { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { label: 'Manage Jobs', href: '/backend/listing', icon: Briefcase },
        { label: 'All Applications', href: '/backend/application', icon: FileText },
        { label: 'Users', href: '/users', icon: Users },
        { label: 'Settings', href: '/settings', icon: Settings },
    ],
};

export default function AppLayout({ children, breadcrumbs: propsBreadcrumbs = [] }: AppLayoutProps) {
    const { url, props } = usePage<{ auth?: { user?: { name: string; email: string } } }>();
    const [mobileOpen, setMobileOpen] = useState(false);
    const userName = props.auth?.user?.name ?? 'Account';
    const role = props.auth?.user?.role ?? 'job_seeker';
    const navItems = navItemsByRole[role] ?? navItemsByRole.job_seeker;

    const breadcrumbs: BreadcrumbItem[] = propsBreadcrumbs.length
        ? propsBreadcrumbs
        : [{ title: url === '/' ? 'Home' : url.split('/').filter(Boolean).map((s) => s.charAt(0).toUpperCase() + s.slice(1)).join(' / ') || 'Home', href: url }];

    const isActive = (href: string) => url === href || (href !== '/dashboard' && url.startsWith(href));

    return (
        <div className="bg-background text-foreground min-h-screen">
            {/* ── Mobile overlay ─────────────────────────────────── */}
            {mobileOpen && <div className="fixed inset-0 z-40 bg-black/50 lg:hidden" onClick={() => setMobileOpen(false)} />}

            {/* ── Sidebar ────────────────────────────────────────── */}
            <aside
                className={cn(
                    'border-sidebar-border bg-sidebar fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r transition-transform duration-200 lg:translate-x-0',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                {/* Brand */}
                <div className="border-sidebar-border flex h-16 items-center justify-between border-b px-4">
                    <Link href="/dashboard" className="flex items-center gap-2">
                        <span className="bg-primary text-primary-foreground grid h-9 w-9 place-items-center rounded-lg">
                            <Briefcase className="h-5 w-5" />
                        </span>
                        <span className="text-sidebar-foreground text-base font-semibold tracking-tight">JobPortal</span>
                    </Link>
                    <button
                        type="button"
                        onClick={() => setMobileOpen(false)}
                        className="text-sidebar-foreground hover:bg-sidebar-accent rounded-md p-1.5 lg:hidden"
                        aria-label="Close menu"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Nav */}
                <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    {navItems.map(({ label, href, icon: Icon }) => {
                        const active = isActive(href);
                        return (
                            <Link
                                key={href}
                                href={href}
                                onClick={() => setMobileOpen(false)}
                                className={cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    active ? 'bg-primary-tint text-primary' : 'text-sidebar-foreground hover:bg-sidebar-accent',
                                )}
                            >
                                <Icon className="h-4 w-4 shrink-0" />
                                {label}
                            </Link>
                        );
                    })}
                </nav>

                {/* User footer */}
                <div className="border-sidebar-border border-t p-3">
                    <div className="flex items-center gap-3 rounded-lg px-2 py-2">
                        <span className="bg-primary text-primary-foreground grid h-8 w-8 place-items-center rounded-full text-xs font-semibold">
                            {userName.charAt(0).toUpperCase()}
                        </span>
                        <div className="min-w-0">
                            <p className="text-sidebar-foreground truncate text-sm font-medium">{userName}</p>
                            <p className="text-muted-foreground truncate text-xs">{props.auth?.user?.email ?? ''}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => router.post(route('logout'))}
                        className="mt-2 w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                    >
                        Logout
                    </button>
                </div>
            </aside>

            {/* ── Main column ────────────────────────────────────── */}
            <div className="flex min-h-screen flex-col lg:pl-64">
                {/* Top bar */}
                <header className="border-border bg-background/80 sticky top-0 z-30 flex h-16 items-center gap-4 border-b px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button
                        type="button"
                        onClick={() => setMobileOpen(true)}
                        className="text-foreground hover:bg-muted rounded-md p-2 lg:hidden"
                        aria-label="Open menu"
                    >
                        <Menu className="h-5 w-5" />
                    </button>

                    {/* Breadcrumbs */}
                    <nav aria-label="Breadcrumb" className="flex min-w-0 flex-1 items-center gap-1.5 text-sm">
                        {breadcrumbs.length === 0 ? (
                            <span className="text-muted-foreground">Home</span>
                        ) : (
                            breadcrumbs.map((crumb, i) => {
                                const last = i === breadcrumbs.length - 1;
                                return (
                                    <span key={crumb.href ?? i} className="flex items-center gap-1.5">
                                        {i > 0 && <ChevronRight className="text-muted-foreground h-3.5 w-3.5" />}
                                        {last ? (
                                            <span className="text-foreground truncate font-medium">{crumb.title}</span>
                                        ) : (
                                            <Link
                                                href={crumb.href}
                                                className="text-muted-foreground hover:text-foreground truncate transition-colors"
                                            >
                                                {crumb.title}
                                            </Link>
                                        )}
                                    </span>
                                );
                            })
                        )}
                    </nav>
                </header>

                {/* Page content */}
                <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <div className="mx-auto w-full max-w-7xl">{children}</div>
                </main>
            </div>
        </div>
    );
}
