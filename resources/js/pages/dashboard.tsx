import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import GA4Controller from '@/actions/App/Http/Controllers/GA4Controller';
import { ReportCard } from '@/components/report-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    BarChart3,
    CheckCircle2,
    Clock,
    Monitor,
    RefreshCw,
    Smartphone,
} from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    Cell,
    Legend,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface SnapshotData {
    // Demographics (existing)
    devices: Array<{ category: string; sessions: number; percentage: number }>;
    channels: Array<{
        channel: string;
        sessions: number;
        conversions: number;
        conversionRate: number;
    }>;
    totals: { sessions: number; conversions: number; conversionRate: number };
    dateRange: { startDate: string; endDate: string };
    // CRO Reports
    funnel: Array<{ step: string; count: number; dropOffRate: number }>;
    exitRates: Array<{
        page: string;
        exits: number;
        pageviews: number;
        exitRate: number;
    }>;
    scrollDepth: Array<{
        page: string;
        scrolled90Percent: number;
        totalViews: number;
        scrollRate: number;
    }>;
    landingPages: {
        desktop: Array<{
            page: string;
            sessions: number;
            conversions: number;
            conversionRate: number;
        }>;
        mobile: Array<{
            page: string;
            sessions: number;
            conversions: number;
            conversionRate: number;
        }>;
    };
    productPages: {
        purchasers: { avgDuration: number; sessions: number };
        nonPurchasers: { avgDuration: number; sessions: number };
    };
    pagesBuckets: Array<{
        bucket: string;
        sessions: number;
        conversions: number;
        conversionRate: number;
    }>;
}

interface Connection {
    property_id: string;
    property_name: string;
    can_generate_report: boolean;
    time_until_next_report: { hours: number; minutes: number } | null;
}

interface Snapshot {
    data: SnapshotData;
    generated_at: string;
    is_expired: boolean;
}

interface Props {
    connection: Connection | null;
    snapshot: Snapshot | null;
}

interface FlashMessages {
    success?: string;
    error?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const DEVICE_COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'];

export default function Dashboard({ connection, snapshot }: Props) {
    const { flash } = usePage<{ flash: FlashMessages }>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-6xl p-6">
                {flash?.success && (
                    <Alert className="mb-6 border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950">
                        <CheckCircle2 className="size-4 text-green-600 dark:text-green-400" />
                        <AlertTitle className="text-green-800 dark:text-green-200">
                            Success
                        </AlertTitle>
                        <AlertDescription className="text-green-700 dark:text-green-300">
                            {flash.success}
                        </AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert variant="destructive" className="mb-6">
                        <AlertCircle className="size-4" />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {!connection ? (
                    <EmptyConnectionState />
                ) : !snapshot ? (
                    <EmptySnapshotState connection={connection} />
                ) : (
                    <DashboardContent
                        connection={connection}
                        snapshot={snapshot}
                    />
                )}
            </div>
        </AppLayout>
    );
}

function EmptyConnectionState() {
    return (
        <Card>
            <CardHeader className="text-center">
                <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary/10">
                    <BarChart3 className="size-8 text-primary" />
                </div>
                <CardTitle className="text-2xl">
                    Connect Google Analytics
                </CardTitle>
                <CardDescription className="text-base">
                    Connect your GA4 property to view demographics reports.
                </CardDescription>
            </CardHeader>
            <CardContent className="text-center">
                <Button asChild>
                    <Link href={GA4Controller.index().url}>Connect GA4</Link>
                </Button>
            </CardContent>
        </Card>
    );
}

function EmptySnapshotState({ connection }: { connection: Connection }) {
    return (
        <Card>
            <CardHeader className="text-center">
                <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary/10">
                    <BarChart3 className="size-8 text-primary" />
                </div>
                <CardTitle className="text-2xl">
                    Generate Your First Report
                </CardTitle>
                <CardDescription className="text-base">
                    Connected to {connection.property_name}. Generate a report
                    to see your analytics data.
                </CardDescription>
            </CardHeader>
            <CardContent className="text-center">
                <Form {...DashboardController.generateSnapshot.form()}>
                    {({ processing }) => (
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <>
                                    <RefreshCw className="mr-2 size-4 animate-spin" />
                                    Generating...
                                </>
                            ) : (
                                <>
                                    <RefreshCw className="mr-2 size-4" />
                                    Generate Report
                                </>
                            )}
                        </Button>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

function DashboardContent({
    connection,
    snapshot,
}: {
    connection: Connection;
    snapshot: Snapshot;
}) {
    const generatedDate = new Date(snapshot.generated_at).toLocaleDateString(
        undefined,
        {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        },
    );

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold">
                        {connection.property_name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Report generated {generatedDate}
                    </p>
                </div>
                <Form {...DashboardController.generateSnapshot.form()}>
                    {({ processing }) => (
                        <Button
                            type="submit"
                            disabled={
                                processing || !connection.can_generate_report
                            }
                            variant={
                                connection.can_generate_report
                                    ? 'default'
                                    : 'outline'
                            }
                        >
                            {processing ? (
                                <>
                                    <RefreshCw className="mr-2 size-4 animate-spin" />
                                    Generating...
                                </>
                            ) : !connection.can_generate_report &&
                              connection.time_until_next_report ? (
                                <>
                                    <Clock className="mr-2 size-4" />
                                    {connection.time_until_next_report.hours}h{' '}
                                    {connection.time_until_next_report.minutes}m
                                </>
                            ) : (
                                <>
                                    <RefreshCw className="mr-2 size-4" />
                                    Refresh Report
                                </>
                            )}
                        </Button>
                    )}
                </Form>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardDescription>Total Sessions</CardDescription>
                        <CardTitle className="text-3xl">
                            {snapshot.data.totals.sessions.toLocaleString()}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-xs text-muted-foreground">
                            Last 30 days
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardDescription>Conversions</CardDescription>
                        <CardTitle className="text-3xl">
                            {snapshot.data.totals.conversions.toLocaleString()}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-xs text-muted-foreground">
                            Last 30 days
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardDescription>Conversion Rate</CardDescription>
                        <CardTitle className="text-3xl">
                            {snapshot.data.totals.conversionRate}%
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-xs text-muted-foreground">
                            Last 30 days
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Device Breakdown</CardTitle>
                        <CardDescription>
                            Sessions by device type (last 30 days)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie
                                    data={snapshot.data.devices}
                                    dataKey="sessions"
                                    nameKey="category"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={100}
                                    label={({ category, percentage }) =>
                                        `${category}: ${percentage}%`
                                    }
                                >
                                    {snapshot.data.devices.map((_, index) => (
                                        <Cell
                                            key={`cell-${index}`}
                                            fill={
                                                DEVICE_COLORS[
                                                    index %
                                                        DEVICE_COLORS.length
                                                ]
                                            }
                                        />
                                    ))}
                                </Pie>
                                <Tooltip
                                    formatter={(value: number) =>
                                        value.toLocaleString()
                                    }
                                />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Traffic Channels</CardTitle>
                        <CardDescription>
                            Sessions by channel (last 30 days)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart
                                data={snapshot.data.channels.slice(0, 6)}
                                layout="vertical"
                            >
                                <XAxis type="number" />
                                <YAxis
                                    dataKey="channel"
                                    type="category"
                                    width={120}
                                    tick={{ fontSize: 12 }}
                                />
                                <Tooltip
                                    formatter={(value: number) =>
                                        value.toLocaleString()
                                    }
                                />
                                <Bar
                                    dataKey="sessions"
                                    fill="#3b82f6"
                                    radius={[0, 4, 4, 0]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>

            {/* CRO Reports */}
            <h2 className="text-xl font-semibold">Conversion Rate Optimization</h2>

            <div className="grid gap-6 lg:grid-cols-2">
                <FunnelChart funnel={snapshot.data.funnel} />
                <PagesBucketsChart pagesBuckets={snapshot.data.pagesBuckets} />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <ProductPagesChart productPages={snapshot.data.productPages} />
                <ScrollDepthChart scrollDepth={snapshot.data.scrollDepth} />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <ExitRatesTable exitRates={snapshot.data.exitRates} />
                <LandingPagesTable landingPages={snapshot.data.landingPages} />
            </div>
        </div>
    );
}

const FUNNEL_COLORS = ['#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#dbeafe'];

function FunnelChart({
    funnel,
}: {
    funnel: SnapshotData['funnel'];
}) {
    if (!funnel || funnel.length === 0) {
        return (
            <ReportCard
                title="Funnel Drop-off Analysis"
                description="Track conversion funnel performance"
            >
                <div className="flex h-[250px] items-center justify-center text-muted-foreground">
                    No funnel data available
                </div>
            </ReportCard>
        );
    }

    return (
        <ReportCard
            title="Funnel Drop-off Analysis"
            description="Track conversion funnel performance"
        >
            <ResponsiveContainer width="100%" height={250}>
                <BarChart data={funnel} layout="vertical">
                    <XAxis type="number" />
                    <YAxis
                        dataKey="step"
                        type="category"
                        width={100}
                        tick={{ fontSize: 11 }}
                    />
                    <Tooltip
                        formatter={(value: number, name: string) => [
                            name === 'count'
                                ? value.toLocaleString()
                                : `${value}%`,
                            name === 'count' ? 'Count' : 'Drop-off',
                        ]}
                    />
                    <Bar dataKey="count" radius={[0, 4, 4, 0]}>
                        {funnel.map((_, index) => (
                            <Cell
                                key={`cell-${index}`}
                                fill={FUNNEL_COLORS[index % FUNNEL_COLORS.length]}
                            />
                        ))}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
            <div className="mt-4 flex flex-wrap gap-2 text-xs">
                {funnel.slice(1).map((step) => (
                    <span
                        key={step.step}
                        className="rounded bg-muted px-2 py-1"
                    >
                        {step.step}: -{step.dropOffRate}% drop-off
                    </span>
                ))}
            </div>
        </ReportCard>
    );
}

function PagesBucketsChart({
    pagesBuckets,
}: {
    pagesBuckets: SnapshotData['pagesBuckets'];
}) {
    if (!pagesBuckets || pagesBuckets.length === 0) {
        return (
            <ReportCard
                title="Conversion by Pages Viewed"
                description="How page depth affects conversion rates"
            >
                <div className="flex h-[250px] items-center justify-center text-muted-foreground">
                    No page view data available
                </div>
            </ReportCard>
        );
    }

    return (
        <ReportCard
            title="Conversion by Pages Viewed"
            description="How page depth affects conversion rates"
        >
            <ResponsiveContainer width="100%" height={250}>
                <BarChart data={pagesBuckets}>
                    <XAxis dataKey="bucket" />
                    <YAxis
                        yAxisId="left"
                        orientation="left"
                        tickFormatter={(v) => v.toLocaleString()}
                    />
                    <YAxis
                        yAxisId="right"
                        orientation="right"
                        tickFormatter={(v) => `${v}%`}
                    />
                    <Tooltip
                        formatter={(value: number, name: string) => [
                            name === 'conversionRate'
                                ? `${value}%`
                                : value.toLocaleString(),
                            name === 'conversionRate'
                                ? 'Conversion Rate'
                                : 'Sessions',
                        ]}
                    />
                    <Legend />
                    <Bar
                        yAxisId="left"
                        dataKey="sessions"
                        fill="#3b82f6"
                        name="Sessions"
                        radius={[4, 4, 0, 0]}
                    />
                    <Bar
                        yAxisId="right"
                        dataKey="conversionRate"
                        fill="#10b981"
                        name="Conversion Rate"
                        radius={[4, 4, 0, 0]}
                    />
                </BarChart>
            </ResponsiveContainer>
        </ReportCard>
    );
}

function ProductPagesChart({
    productPages,
}: {
    productPages: SnapshotData['productPages'];
}) {
    if (!productPages) {
        return (
            <ReportCard
                title="Time on Product Pages"
                description="Compare session duration by purchase behavior"
            >
                <div className="flex h-[200px] items-center justify-center text-muted-foreground">
                    No product page data available
                </div>
            </ReportCard>
        );
    }

    const formatDuration = (seconds: number) => {
        const mins = Math.floor(seconds / 60);
        const secs = Math.round(seconds % 60);
        return `${mins}m ${secs}s`;
    };

    const data = [
        {
            name: 'Purchasers',
            duration: productPages.purchasers.avgDuration,
            sessions: productPages.purchasers.sessions,
        },
        {
            name: 'Non-Purchasers',
            duration: productPages.nonPurchasers.avgDuration,
            sessions: productPages.nonPurchasers.sessions,
        },
    ];

    return (
        <ReportCard
            title="Time on Product Pages"
            description="Compare session duration by purchase behavior"
        >
            <ResponsiveContainer width="100%" height={200}>
                <BarChart data={data}>
                    <XAxis dataKey="name" />
                    <YAxis tickFormatter={(v) => `${Math.round(v / 60)}m`} />
                    <Tooltip
                        formatter={(value: number) => [
                            formatDuration(value),
                            'Avg Duration',
                        ]}
                    />
                    <Bar dataKey="duration" radius={[4, 4, 0, 0]}>
                        <Cell fill="#10b981" />
                        <Cell fill="#ef4444" />
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
            <div className="mt-2 flex justify-center gap-6 text-sm text-muted-foreground">
                <span>
                    Purchasers:{' '}
                    {formatDuration(productPages.purchasers.avgDuration)} avg (
                    {productPages.purchasers.sessions.toLocaleString()} sessions)
                </span>
            </div>
        </ReportCard>
    );
}

function ScrollDepthChart({
    scrollDepth,
}: {
    scrollDepth: SnapshotData['scrollDepth'];
}) {
    if (!scrollDepth || scrollDepth.length === 0) {
        return (
            <ReportCard
                title="Scroll Depth"
                description="Percentage of visitors who scroll 90%+"
            >
                <div className="flex h-[200px] items-center justify-center text-muted-foreground">
                    No scroll tracking data available
                </div>
            </ReportCard>
        );
    }

    return (
        <ReportCard
            title="Scroll Depth"
            description="Percentage of visitors who scroll 90%+"
        >
            <ResponsiveContainer width="100%" height={200}>
                <BarChart data={scrollDepth.slice(0, 6)} layout="vertical">
                    <XAxis type="number" domain={[0, 100]} unit="%" />
                    <YAxis
                        dataKey="page"
                        type="category"
                        width={100}
                        tick={{ fontSize: 10 }}
                        tickFormatter={(v) =>
                            v.length > 15 ? `${v.slice(0, 15)}...` : v
                        }
                    />
                    <Tooltip
                        formatter={(value: number) => [`${value}%`, '90%+ Scroll Rate']}
                    />
                    <Bar
                        dataKey="scrollRate"
                        fill="#8b5cf6"
                        radius={[0, 4, 4, 0]}
                    />
                </BarChart>
            </ResponsiveContainer>
        </ReportCard>
    );
}

function ExitRatesTable({
    exitRates,
}: {
    exitRates: SnapshotData['exitRates'];
}) {
    const columns = [
        {
            key: 'page' as const,
            header: 'Page',
            className: 'max-w-[150px] truncate',
        },
        {
            key: 'pageviews' as const,
            header: 'Views',
            className: 'text-right',
            render: (value: number) => value.toLocaleString(),
        },
        {
            key: 'exits' as const,
            header: 'Exits',
            className: 'text-right',
            render: (value: number) => value.toLocaleString(),
        },
        {
            key: 'exitRate' as const,
            header: 'Exit Rate',
            className: 'text-right',
            render: (value: number) => (
                <span
                    className={cn(
                        value > 40
                            ? 'text-red-600 dark:text-red-400'
                            : value > 25
                              ? 'text-yellow-600 dark:text-yellow-400'
                              : 'text-green-600 dark:text-green-400',
                    )}
                >
                    {value}%
                </span>
            ),
        },
    ];

    return (
        <ReportCard
            title="Exit Rate by Page"
            description="Pages where visitors leave your site"
        >
            <DataTable
                columns={columns}
                data={exitRates?.slice(0, 8) ?? []}
                emptyMessage="No exit rate data available"
            />
        </ReportCard>
    );
}

function LandingPagesTable({
    landingPages,
}: {
    landingPages: SnapshotData['landingPages'];
}) {
    const [activeTab, setActiveTab] = useState<'desktop' | 'mobile'>('desktop');

    const columns = [
        {
            key: 'page' as const,
            header: 'Page',
            className: 'max-w-[120px] truncate',
        },
        {
            key: 'sessions' as const,
            header: 'Sessions',
            className: 'text-right',
            render: (value: number) => value.toLocaleString(),
        },
        {
            key: 'conversions' as const,
            header: 'Conv.',
            className: 'text-right',
            render: (value: number) => value.toLocaleString(),
        },
        {
            key: 'conversionRate' as const,
            header: 'Rate',
            className: 'text-right',
            render: (value: number) => `${value}%`,
        },
    ];

    const data = activeTab === 'desktop'
        ? landingPages?.desktop ?? []
        : landingPages?.mobile ?? [];

    return (
        <ReportCard
            title="Landing Pages by Device"
            description="Top landing pages split by device type"
        >
            <div className="mb-4 flex gap-2">
                <button
                    onClick={() => setActiveTab('desktop')}
                    className={cn(
                        'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors',
                        activeTab === 'desktop'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted hover:bg-muted/80',
                    )}
                >
                    <Monitor className="size-4" />
                    Desktop
                </button>
                <button
                    onClick={() => setActiveTab('mobile')}
                    className={cn(
                        'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors',
                        activeTab === 'mobile'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted hover:bg-muted/80',
                    )}
                >
                    <Smartphone className="size-4" />
                    Mobile
                </button>
            </div>
            <DataTable
                columns={columns}
                data={data.slice(0, 5)}
                emptyMessage="No landing page data available"
            />
        </ReportCard>
    );
}

export function DashboardSkeleton() {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div className="space-y-2">
                    <Skeleton className="h-8 w-48" />
                    <Skeleton className="h-4 w-32" />
                </div>
                <Skeleton className="h-10 w-32" />
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                {[1, 2, 3].map((i) => (
                    <Card key={i}>
                        <CardHeader className="pb-2">
                            <Skeleton className="h-4 w-24" />
                            <Skeleton className="mt-2 h-8 w-20" />
                        </CardHeader>
                    </Card>
                ))}
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                {[1, 2].map((i) => (
                    <Card key={i}>
                        <CardHeader>
                            <Skeleton className="h-6 w-32" />
                            <Skeleton className="mt-1 h-4 w-48" />
                        </CardHeader>
                        <CardContent>
                            <Skeleton className="h-[300px] w-full" />
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
