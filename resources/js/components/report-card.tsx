import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { AlertCircle } from 'lucide-react';
import { type ReactNode } from 'react';

interface ReportCardProps {
    title: string;
    description?: string;
    loading?: boolean;
    error?: string;
    children: ReactNode;
    className?: string;
}

export function ReportCard({
    title,
    description,
    loading = false,
    error,
    children,
    className,
}: ReportCardProps) {
    return (
        <Card className={className}>
            <CardHeader>
                <CardTitle className="text-lg">{title}</CardTitle>
                {description && (
                    <CardDescription>{description}</CardDescription>
                )}
            </CardHeader>
            <CardContent>
                {loading ? (
                    <ReportCardSkeleton />
                ) : error ? (
                    <ReportCardError message={error} />
                ) : (
                    children
                )}
            </CardContent>
        </Card>
    );
}

function ReportCardSkeleton() {
    return (
        <div className="space-y-3">
            <Skeleton className="h-[200px] w-full" />
        </div>
    );
}

function ReportCardError({ message }: { message: string }) {
    return (
        <div className="flex h-[200px] flex-col items-center justify-center gap-2 text-muted-foreground">
            <AlertCircle className="size-8" />
            <p className="text-sm">{message}</p>
        </div>
    );
}
