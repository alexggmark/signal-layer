import GA4Controller from '@/actions/App/Http/Controllers/GA4Controller';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    BarChart3,
    CheckCircle2,
    Clock,
    ExternalLink,
    Unlink,
} from 'lucide-react';

interface GA4Connection {
    property_id: string;
    property_name: string;
    connected_at: string;
    can_generate_report: boolean;
    time_until_next_report: { hours: number; minutes: number } | null;
}

interface Props {
    connection: GA4Connection | null;
}

interface FlashMessages {
    success?: string;
    error?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Google Analytics',
        href: GA4Controller.index().url,
    },
];

export default function GA4Index({ connection }: Props) {
    const { flash } = usePage<{ flash: FlashMessages }>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Google Analytics" />

            <div className="mx-auto max-w-2xl p-6">
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

                {connection ? (
                    <ConnectedState connection={connection} />
                ) : (
                    <DisconnectedState />
                )}
            </div>
        </AppLayout>
    );
}

function DisconnectedState() {
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
                    Connect your GA4 property to generate AI-powered reports
                    about your website traffic and user behavior.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="rounded-lg bg-muted/50 p-4">
                    <h4 className="mb-2 font-medium">What you'll get:</h4>
                    <ul className="space-y-2 text-sm text-muted-foreground">
                        <li className="flex items-center gap-2">
                            <CheckCircle2 className="size-4 text-green-500" />
                            AI-generated insights from your analytics data
                        </li>
                        <li className="flex items-center gap-2">
                            <CheckCircle2 className="size-4 text-green-500" />
                            Automated traffic and engagement reports
                        </li>
                        <li className="flex items-center gap-2">
                            <CheckCircle2 className="size-4 text-green-500" />
                            Secure read-only access to your GA4 data
                        </li>
                    </ul>
                </div>
            </CardContent>
            <CardFooter>
                <Button asChild className="w-full">
                    <Link href={GA4Controller.connect().url}>
                        <svg
                            className="mr-2 size-5"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                        >
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Connect with Google
                    </Link>
                </Button>
            </CardFooter>
        </Card>
    );
}

function ConnectedState({ connection }: { connection: GA4Connection }) {
    const connectedDate = new Date(connection.connected_at).toLocaleDateString(
        undefined,
        {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        },
    );

    return (
        <Card>
            <CardHeader>
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex size-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                            <CheckCircle2 className="size-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <CardTitle>Connected</CardTitle>
                            <CardDescription>
                                Your GA4 property is connected
                            </CardDescription>
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="rounded-lg border p-4">
                    <div className="mb-1 text-sm text-muted-foreground">
                        Property
                    </div>
                    <div className="flex items-center gap-2 font-medium">
                        <BarChart3 className="size-4" />
                        {connection.property_name}
                    </div>
                    <div className="mt-2 text-xs text-muted-foreground">
                        ID: {connection.property_id}
                    </div>
                </div>

                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Clock className="size-4" />
                    Connected on {connectedDate}
                </div>

                {connection.time_until_next_report && (
                    <Alert>
                        <Clock className="size-4" />
                        <AlertTitle>Rate limit active</AlertTitle>
                        <AlertDescription>
                            You can generate your next report in{' '}
                            {connection.time_until_next_report.hours}h{' '}
                            {connection.time_until_next_report.minutes}m
                        </AlertDescription>
                    </Alert>
                )}
            </CardContent>
            <CardFooter className="flex flex-col gap-3 sm:flex-row">
                <Button variant="outline" asChild className="w-full sm:w-auto">
                    <a
                        href={`https://analytics.google.com/analytics/web/#/${connection.property_id.replace('properties/', 'p')}`}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink className="mr-2 size-4" />
                        Open in GA4
                    </a>
                </Button>
                <Form
                    {...GA4Controller.disconnect.form()}
                    className="w-full sm:w-auto"
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                            className="w-full"
                        >
                            <Unlink className="mr-2 size-4" />
                            {processing ? 'Disconnecting...' : 'Disconnect'}
                        </Button>
                    )}
                </Form>
            </CardFooter>
        </Card>
    );
}
