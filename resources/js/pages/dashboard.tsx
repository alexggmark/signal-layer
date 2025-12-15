import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import GA4Controller from '@/actions/App/Http/Controllers/GA4Controller';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    BarChart3,
    CheckCircle2,
    Clock,
    RefreshCw,
} from 'lucide-react';
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
    devices: Array<{ category: string; sessions: number; percentage: number }>;
    channels: Array<{
        channel: string;
        sessions: number;
        conversions: number;
        conversionRate: number;
    }>;
    totals: { sessions: number; conversions: number; conversionRate: number };
    dateRange: { startDate: string; endDate: string };
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
        </div>
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
