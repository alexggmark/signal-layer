import GA4Controller from '@/actions/App/Http/Controllers/GA4Controller';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, BarChart3, Building2 } from 'lucide-react';
import { useState } from 'react';

interface GA4Property {
    property_id: string;
    property_name: string;
    account_name: string;
}

interface Props {
    properties: GA4Property[];
}

interface FlashMessages {
    error?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Google Analytics',
        href: GA4Controller.index().url,
    },
    {
        title: 'Select Property',
        href: GA4Controller.selectProperty().url,
    },
];

export default function SelectProperty({ properties }: Props) {
    const { flash } = usePage<{ flash: FlashMessages }>().props;
    const [selectedProperty, setSelectedProperty] =
        useState<GA4Property | null>(null);

    const groupedProperties = properties.reduce(
        (acc, property) => {
            if (!acc[property.account_name]) {
                acc[property.account_name] = [];
            }
            acc[property.account_name].push(property);
            return acc;
        },
        {} as Record<string, GA4Property[]>,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Select GA4 Property" />

            <div className="mx-auto max-w-2xl p-6">
                {flash?.error && (
                    <Alert variant="destructive" className="mb-6">
                        <AlertCircle className="size-4" />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-3">
                            <div className="flex size-12 items-center justify-center rounded-full bg-primary/10">
                                <BarChart3 className="size-6 text-primary" />
                            </div>
                            <div>
                                <CardTitle>Select a Property</CardTitle>
                                <CardDescription>
                                    Choose which GA4 property you want to
                                    connect
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    {properties.length === 0 ? (
                        <CardContent>
                            <Alert>
                                <AlertCircle className="size-4" />
                                <AlertTitle>No properties found</AlertTitle>
                                <AlertDescription>
                                    We couldn't find any GA4 properties
                                    associated with your Google account. Make
                                    sure you have access to at least one GA4
                                    property.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    ) : (
                        <Form {...GA4Controller.storeProperty.form()}>
                            {({ processing, errors }) => (
                                <>
                                    <CardContent className="space-y-6">
                                        <input
                                            type="hidden"
                                            name="property_id"
                                            value={
                                                selectedProperty?.property_id ||
                                                ''
                                            }
                                        />
                                        <input
                                            type="hidden"
                                            name="property_name"
                                            value={
                                                selectedProperty?.property_name ||
                                                ''
                                            }
                                        />

                                        {Object.entries(groupedProperties).map(
                                            ([accountName, accountProperties]) => (
                                                <div
                                                    key={accountName}
                                                    className="space-y-3"
                                                >
                                                    <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                                        <Building2 className="size-4" />
                                                        {accountName}
                                                    </div>
                                                    <div className="space-y-2">
                                                        {accountProperties.map(
                                                            (property) => (
                                                                <PropertyOption
                                                                    key={
                                                                        property.property_id
                                                                    }
                                                                    property={
                                                                        property
                                                                    }
                                                                    isSelected={
                                                                        selectedProperty?.property_id ===
                                                                        property.property_id
                                                                    }
                                                                    onSelect={() =>
                                                                        setSelectedProperty(
                                                                            property,
                                                                        )
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            ),
                                        )}

                                        <InputError
                                            message={errors.property_id}
                                        />
                                    </CardContent>

                                    <CardFooter className="flex flex-col gap-3 sm:flex-row">
                                        <Button
                                            variant="outline"
                                            asChild
                                            className="w-full sm:w-auto"
                                        >
                                            <Link
                                                href={GA4Controller.index().url}
                                            >
                                                <ArrowLeft className="mr-2 size-4" />
                                                Cancel
                                            </Link>
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing || !selectedProperty
                                            }
                                            className="w-full sm:flex-1"
                                        >
                                            {processing
                                                ? 'Connecting...'
                                                : 'Connect Property'}
                                        </Button>
                                    </CardFooter>
                                </>
                            )}
                        </Form>
                    )}

                    {properties.length === 0 && (
                        <CardFooter>
                            <Button variant="outline" asChild className="w-full">
                                <Link href={GA4Controller.index().url}>
                                    <ArrowLeft className="mr-2 size-4" />
                                    Go Back
                                </Link>
                            </Button>
                        </CardFooter>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}

function PropertyOption({
    property,
    isSelected,
    onSelect,
}: {
    property: GA4Property;
    isSelected: boolean;
    onSelect: () => void;
}) {
    return (
        <div
            className={`relative flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors ${
                isSelected
                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                    : 'border-border hover:border-primary/50 hover:bg-muted/50'
            }`}
            onClick={onSelect}
        >
            <div
                className={`flex size-5 shrink-0 items-center justify-center rounded-full border-2 ${
                    isSelected ? 'border-primary' : 'border-muted-foreground/30'
                }`}
            >
                {isSelected && (
                    <div className="size-2.5 rounded-full bg-primary" />
                )}
            </div>
            <div className="min-w-0 flex-1">
                <Label
                    className="cursor-pointer font-medium"
                    onClick={(e) => e.stopPropagation()}
                >
                    {property.property_name}
                </Label>
                <p className="truncate text-xs text-muted-foreground">
                    {property.property_id}
                </p>
            </div>
        </div>
    );
}
