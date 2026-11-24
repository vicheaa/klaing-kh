import { Form, Link, router } from '@inertiajs/react';
import {
    ArrowDownRight,
    ArrowDownToLine,
    ArrowLeft,
    ArrowRight,
    ArrowUpRight,
    Braces,
    Building2,
    ChevronDown,
    House,
    Layers3,
    Map,
    MapPin,
    Search,
} from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import PublicLayout, {
    LanguageText,
    PublicPageHeading,
} from '@/layouts/public-layout';
import type { PublicLanguage } from '@/layouts/public-layout';
import { index } from '@/routes/administrative-areas';
import {
    index as apiIndex,
    show as apiShow,
} from '@/routes/api/v1/administrative-areas';

const levels = ['province', 'district', 'commune', 'village'] as const;
type Level = (typeof levels)[number];
type AncestorLevel = Exclude<Level, 'village'>;

const ancestors: Record<Level, AncestorLevel[]> = {
    province: [],
    district: ['province'],
    commune: ['province', 'district'],
    village: ['province', 'district', 'commune'],
};

const levelIcons = {
    province: Map,
    district: Building2,
    commune: Layers3,
    village: House,
};
const csvFiles = {
    province: 'CambodiaProvinceList2025.csv',
    district: 'CambodiaDistrictList2025.csv',
    commune: 'CambodiaCommuneList2025.csv',
    village: 'CambodiaVillagesList2025.csv',
};

const translations = {
    en: {
        title: 'Administrative areas',
        description:
            'Search Cambodian place names and codes in Khmer or English.',
        dataset: 'dataset',
        levels: {
            province: 'Provinces',
            district: 'Districts',
            commune: 'Communes',
            village: 'Villages',
        },
        areaTypes: {
            province: 'Province',
            district: 'District',
            commune: 'Commune',
            village: 'Village',
        },
        all: {
            province: 'All provinces',
            district: 'All districts',
            commune: 'All communes',
        },
        chooseProvince: 'Choose a province first',
        chooseDistrict: 'Choose a district first',
        search: 'Search all areas by name or code',
        searchHint: 'e.g. Battambang or 02',
        submit: 'Search',
        searchResults: 'Search results',
        allResults: 'All levels',
        clear: 'Clear filters',
        code: 'Code',
        name: 'Name',
        actions: 'Actions',
        within: 'Administrative hierarchy',
        shown: (from: string, to: string, total: string) =>
            `Showing ${from}–${to} of ${total}`,
        results: (total: string) => `${total} results`,
        browse: (level: string, place: string) =>
            `Browse ${level.toLowerCase()} in ${place}`,
        viewJson: (place: string) => `View JSON for ${place}`,
        api: 'JSON API',
        download: 'Download CSV',
        missing: 'Missing from commune list',
        unavailable: 'The dataset has not been seeded yet.',
        empty: 'No areas match these filters.',
        previous: 'Previous',
        next: 'Next',
        pagination: 'Pagination',
        loading: 'Loading…',
    },
    km: {
        title: 'តំបន់រដ្ឋបាល',
        description: 'ស្វែងរកតាមឈ្មោះ ឬលេខកូដ ជាភាសាខ្មែរ ឬអង់គ្លេស។',
        dataset: 'ទិន្នន័យឆ្នាំ',
        levels: {
            province: 'រាជធានី និងខេត្ត',
            district: 'ក្រុង ស្រុក និងខណ្ឌ',
            commune: 'ឃុំ និងសង្កាត់',
            village: 'ភូមិ',
        },
        areaTypes: {
            province: 'រាជធានី ឬខេត្ត',
            district: 'ក្រុង ស្រុក ឬខណ្ឌ',
            commune: 'ឃុំ ឬសង្កាត់',
            village: 'ភូមិ',
        },
        all: {
            province: 'គ្រប់រាជធានី និងខេត្ត',
            district: 'គ្រប់ក្រុង ស្រុក និងខណ្ឌ',
            commune: 'គ្រប់ឃុំ និងសង្កាត់',
        },
        chooseProvince: 'ជ្រើសរើសរាជធានី ឬខេត្តជាមុន',
        chooseDistrict: 'ជ្រើសរើសក្រុង ស្រុក ឬខណ្ឌជាមុន',
        search: 'ស្វែងរកគ្រប់តំបន់តាមឈ្មោះ ឬលេខកូដ',
        searchHint: 'ឧ. បាត់ដំបង ឬ 02',
        submit: 'ស្វែងរក',
        searchResults: 'លទ្ធផលស្វែងរក',
        allResults: 'គ្រប់ថ្នាក់',
        clear: 'សម្អាតតម្រង',
        code: 'លេខកូដ',
        name: 'ឈ្មោះ',
        actions: 'សកម្មភាព',
        within: 'លំដាប់រដ្ឋបាល',
        shown: (from: string, to: string, total: string) =>
            `បង្ហាញ ${from}–${to} នៃ ${total}`,
        results: (total: string) => `លទ្ធផល ${total}`,
        browse: (level: string, place: string) => `មើល${level}ក្នុង ${place}`,
        viewJson: (place: string) => `មើល JSON សម្រាប់ ${place}`,
        api: 'JSON API',
        download: 'ទាញយក CSV',
        missing: 'មិនមានក្នុងបញ្ជីឃុំ និងសង្កាត់',
        unavailable: 'មិនទាន់បានបញ្ចូលទិន្នន័យ។',
        empty: 'រកមិនឃើញតំបន់ដែលត្រូវនឹងតម្រងនេះទេ។',
        previous: 'មុន',
        next: 'បន្ទាប់',
        pagination: 'ទំព័រលទ្ធផល',
        loading: 'កំពុងផ្ទុក…',
    },
};

type PlaceReference = {
    code: string;
    name_en: string | null;
    name_km: string | null;
};

type Area = Record<AncestorLevel, PlaceReference | null> &
    Record<`${AncestorLevel}_code`, string | null> & {
        id: string;
        level: Level;
        code: string;
        name_en: string;
        name_km: string;
    };

type Filters = Record<`${AncestorLevel}_code`, string | null> & {
    level: Level | null;
    q: string | null;
};

type PageProps = {
    areas: Area[];
    counts: Record<Level, number>;
    filters: Filters;
    options: Record<AncestorLevel, PlaceReference[]>;
    pagination: {
        page: number;
        last_page: number;
        first_item: number | null;
        last_item: number | null;
        total: number;
    };
    datasetYear: number;
    language: PublicLanguage;
};

export default function AdministrativeAreas({
    areas,
    counts,
    filters,
    options,
    pagination,
    datasetYear,
    language,
}: PageProps) {
    const copy = translations[language];
    const alternate = language === 'en' ? 'km' : 'en';
    const otherName = alternate === 'en' ? 'name_en' : 'name_km';
    const name = language === 'en' ? 'name_en' : 'name_km';
    const { level, q } = filters;
    const scope = { ...filters, lang: language };
    const resultsTitle = (locale: PublicLanguage) =>
        level === null
            ? translations[locale].searchResults
            : translations[locale].levels[level];
    const format = new Intl.NumberFormat(language);
    const hasData = levels.some((type) => counts[type] > 0);
    const hasFilters = Boolean(
        q ||
        filters.province_code ||
        filters.district_code ||
        filters.commune_code,
    );
    function summary(locale: PublicLanguage) {
        const numbers = new Intl.NumberFormat(locale);
        const copy = translations[locale];

        return pagination.first_item !== null && pagination.last_item !== null
            ? copy.shown(
                  numbers.format(pagination.first_item),
                  numbers.format(pagination.last_item),
                  numbers.format(pagination.total),
              )
            : copy.results(numbers.format(pagination.total));
    }

    function changeAncestor(type: AncestorLevel, code: string | null) {
        const query = { ...scope, [`${type}_code`]: code };
        if (type === 'province') {
            query.district_code = null;
        }
        if (type !== 'commune') {
            query.commune_code = null;
        }
        router.get(index.url({ query }), {}, { preserveScroll: true });
    }

    function childList(area: Area, child: Level) {
        return index({
            query: {
                lang: language,
                level: child,
                province_code:
                    area.level === 'province' ? area.code : area.province_code,
                district_code:
                    area.level === 'district' ? area.code : area.district_code,
                commune_code: area.level === 'commune' ? area.code : null,
            },
        });
    }

    return (
        <PublicLayout
            title={copy.title}
            language={language}
            section="areas"
            languageHref={index({
                query: { ...scope, lang: alternate, page: pagination.page },
            })}
        >
            <PublicPageHeading
                icon={MapPin}
                title={copy.title}
                description={copy.description}
            >
                <Badge variant="outline" className="bg-background">
                    {language === 'en'
                        ? `${datasetYear} ${copy.dataset}`
                        : `${copy.dataset} ${datasetYear}`}
                </Badge>
            </PublicPageHeading>

            <nav
                aria-label={copy.within}
                className="grid grid-cols-2 gap-3 lg:grid-cols-4"
            >
                {levels.map((type) => {
                    const Icon = levelIcons[type];
                    const active = type === level;
                    return (
                        <Link
                            key={type}
                            href={index({
                                query: { level: type, lang: language, q },
                            })}
                            aria-current={active ? 'true' : undefined}
                            preserveScroll
                            className={cn(
                                'group flex min-w-0 flex-wrap items-start gap-2.5 rounded-xl border p-3 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring sm:flex-nowrap sm:gap-3 sm:p-4',
                                active
                                    ? 'border-ring bg-accent'
                                    : 'border-border bg-background hover:border-ring',
                            )}
                        >
                            <span
                                className={cn(
                                    'grid size-8 shrink-0 place-items-center rounded-lg sm:size-10',
                                    active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-accent text-accent-foreground',
                                )}
                            >
                                <Icon
                                    aria-hidden="true"
                                    className="size-4 sm:size-5"
                                />
                            </span>
                            <span className="grid min-w-0 grow basis-full gap-1 sm:basis-auto">
                                <span className="text-xs leading-relaxed font-medium text-muted-foreground sm:text-sm">
                                    <LanguageText
                                        language={language}
                                        en={translations.en.levels[type]}
                                        km={translations.km.levels[type]}
                                    />
                                </span>
                                <span className="text-xl leading-none font-semibold tracking-tight tabular-nums sm:text-2xl">
                                    {format.format(counts[type])}
                                </span>
                            </span>
                            <ArrowDownRight
                                aria-hidden="true"
                                className="ml-auto hidden size-4 shrink-0 text-primary group-hover:translate-x-0.5 sm:block"
                            />
                        </Link>
                    );
                })}
            </nav>

            <section
                aria-labelledby="area-results"
                className="overflow-hidden rounded-2xl border border-border bg-background"
            >
                <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
                    <div className="grid gap-1">
                        <h2
                            id="area-results"
                            className="text-xl leading-relaxed font-semibold sm:text-2xl"
                        >
                            <LanguageText
                                language={language}
                                en={resultsTitle('en')}
                                km={resultsTitle('km')}
                            />
                        </h2>
                        <p
                            role="status"
                            className="text-sm text-muted-foreground"
                        >
                            <LanguageText
                                language={language}
                                en={summary('en')}
                                km={summary('km')}
                            />
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" size="sm">
                            <a href={apiIndex.url({ query: filters })}>
                                <Braces aria-hidden="true" />
                                {copy.api}
                            </a>
                        </Button>
                        {level !== null && (
                            <Button asChild variant="outline" size="sm">
                                <a href={`/assets/${csvFiles[level]}`} download>
                                    <ArrowDownToLine aria-hidden="true" />
                                    <LanguageText
                                        language={language}
                                        en={translations.en.download}
                                        km={translations.km.download}
                                    />
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-end gap-3 border-y border-border bg-muted/60 px-4 py-4 sm:px-6">
                    <Form
                        key={q ?? ''}
                        action={index({ query: { lang: language } })}
                        options={{ preserveScroll: true }}
                        className="grid min-w-0 grow basis-72 gap-2"
                    >
                        {({ errors, processing }) => (
                            <>
                                <Label htmlFor="area-search">
                                    <LanguageText
                                        language={language}
                                        en={translations.en.search}
                                        km={translations.km.search}
                                    />
                                </Label>
                                <div className="flex gap-2">
                                    <div className="relative min-w-0 grow">
                                        <Search
                                            aria-hidden="true"
                                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                        />
                                        <Input
                                            id="area-search"
                                            name="q"
                                            type="search"
                                            maxLength={100}
                                            defaultValue={q ?? ''}
                                            placeholder={copy.searchHint}
                                            className="bg-background pl-9"
                                            aria-invalid={Boolean(errors.q)}
                                            aria-describedby={
                                                errors.q
                                                    ? 'area-search-error'
                                                    : undefined
                                            }
                                        />
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        <LanguageText
                                            language={language}
                                            en={
                                                processing
                                                    ? translations.en.loading
                                                    : translations.en.submit
                                            }
                                            km={
                                                processing
                                                    ? translations.km.loading
                                                    : translations.km.submit
                                            }
                                        />
                                    </Button>
                                </div>
                                <InputError
                                    id="area-search-error"
                                    message={errors.q}
                                />
                            </>
                        )}
                    </Form>
                    {level !== null &&
                        ancestors[level].map((type) => {
                            const disabled =
                                (type === 'district' &&
                                    !filters.province_code) ||
                                (type === 'commune' && !filters.district_code);
                            return (
                                <div
                                    key={type}
                                    className="grid min-w-0 grow basis-36 gap-2"
                                >
                                    <Label htmlFor={`area-${type}`}>
                                        <LanguageText
                                            language={language}
                                            en={translations.en.areaTypes[type]}
                                            km={translations.km.areaTypes[type]}
                                        />
                                    </Label>
                                    <div className="relative">
                                        <select
                                            id={`area-${type}`}
                                            value={
                                                filters[`${type}_code`] ?? ''
                                            }
                                            onChange={(event) =>
                                                changeAncestor(
                                                    type,
                                                    event.target.value || null,
                                                )
                                            }
                                            disabled={disabled}
                                            className="h-9 w-full appearance-none truncate rounded-md border border-input bg-background py-1 pr-8 pl-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-60"
                                        >
                                            <option value="">
                                                {disabled
                                                    ? type === 'district'
                                                        ? copy.chooseProvince
                                                        : copy.chooseDistrict
                                                    : copy.all[type]}
                                            </option>
                                            {options[type].map((place) => (
                                                <option
                                                    key={place.code}
                                                    value={place.code}
                                                >
                                                    {place[name] ??
                                                        `${place.code} · ${copy.missing}`}
                                                </option>
                                            ))}
                                        </select>
                                        <ChevronDown
                                            aria-hidden="true"
                                            className="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    {q !== null && level !== null && (
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={index({
                                    query: { q, lang: language },
                                })}
                                preserveScroll
                            >
                                <LanguageText
                                    language={language}
                                    en={translations.en.allResults}
                                    km={translations.km.allResults}
                                />
                            </Link>
                        </Button>
                    )}
                    {hasFilters && (
                        <Button asChild variant="ghost" size="sm">
                            <Link
                                href={index({
                                    query: { level, lang: language },
                                })}
                                preserveScroll
                            >
                                <LanguageText
                                    language={language}
                                    en={translations.en.clear}
                                    km={translations.km.clear}
                                />
                            </Link>
                        </Button>
                    )}
                </div>

                {areas.length === 0 ? (
                    <p className="px-5 py-12 text-center text-sm text-muted-foreground">
                        {hasData ? copy.empty : copy.unavailable}
                    </p>
                ) : (
                    <table className="w-full text-left text-sm">
                        <caption className="sr-only">
                            {resultsTitle(language)}
                        </caption>
                        <thead className="border-b border-border bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th
                                    scope="col"
                                    className="px-4 py-3 font-semibold sm:px-6"
                                >
                                    <LanguageText
                                        language={language}
                                        en={translations.en.name}
                                        km={translations.km.name}
                                    />
                                </th>
                                <th scope="col" className="py-3 font-semibold">
                                    <LanguageText
                                        language={language}
                                        en={translations.en.code}
                                        km={translations.km.code}
                                    />
                                </th>
                                {level !== 'province' && (
                                    <th
                                        scope="col"
                                        className="hidden px-4 py-3 font-semibold md:table-cell"
                                    >
                                        <LanguageText
                                            language={language}
                                            en={translations.en.within}
                                            km={translations.km.within}
                                        />
                                    </th>
                                )}
                                <th scope="col" className="w-12 sm:w-20">
                                    <span className="sr-only">
                                        {copy.actions}
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {areas.map((area) => {
                                const nextLevel =
                                    levels[levels.indexOf(area.level) + 1];
                                const hierarchyFor = (locale: PublicLanguage) =>
                                    ancestors[area.level]
                                        .map((type) => {
                                            const code = area[`${type}_code`];
                                            return (
                                                area[type]?.[
                                                    `name_${locale}`
                                                ] ??
                                                (code &&
                                                    `${code} · ${translations[locale].missing}`)
                                            );
                                        })
                                        .filter(Boolean)
                                        .join(' / ');
                                const hierarchy = ancestors[area.level].length >
                                    0 && (
                                    <LanguageText
                                        language={language}
                                        en={hierarchyFor('en')}
                                        km={hierarchyFor('km')}
                                    />
                                );
                                return (
                                    <tr
                                        key={area.id}
                                        className="hover:bg-muted/60"
                                    >
                                        <th
                                            scope="row"
                                            className="px-4 py-3.5 font-medium sm:px-6"
                                        >
                                            <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                <span className="text-base leading-relaxed">
                                                    {area[name]}
                                                </span>
                                                {level === null && (
                                                    <Badge variant="secondary">
                                                        {
                                                            copy.areaTypes[
                                                                area.level
                                                            ]
                                                        }
                                                    </Badge>
                                                )}
                                            </div>
                                            <span
                                                className="block text-xs font-normal text-muted-foreground"
                                                lang={alternate}
                                            >
                                                {area[otherName]}
                                            </span>
                                            {hierarchy && (
                                                <span className="mt-1 block text-xs leading-relaxed font-normal text-muted-foreground md:hidden">
                                                    {hierarchy}
                                                </span>
                                            )}
                                        </th>
                                        <td className="py-3.5">
                                            <span className="rounded-md bg-secondary px-1.5 py-1 font-mono text-xs text-secondary-foreground">
                                                {area.code}
                                            </span>
                                        </td>
                                        {level !== 'province' && (
                                            <td className="hidden px-4 py-3.5 text-xs leading-relaxed text-muted-foreground md:table-cell">
                                                {hierarchy}
                                            </td>
                                        )}
                                        <td className="pr-2.5 text-right sm:pr-6">
                                            <Button
                                                asChild
                                                size="icon"
                                                variant="ghost"
                                                className="text-primary"
                                            >
                                                {nextLevel ? (
                                                    <Link
                                                        href={childList(
                                                            area,
                                                            nextLevel,
                                                        )}
                                                        aria-label={copy.browse(
                                                            copy.levels[
                                                                nextLevel
                                                            ],
                                                            area[name],
                                                        )}
                                                        preserveScroll
                                                    >
                                                        <ArrowRight aria-hidden="true" />
                                                    </Link>
                                                ) : (
                                                    <a
                                                        href={apiShow.url(
                                                            area.id,
                                                        )}
                                                        aria-label={copy.viewJson(
                                                            area[name],
                                                        )}
                                                    >
                                                        <ArrowUpRight aria-hidden="true" />
                                                    </a>
                                                )}
                                            </Button>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                )}

                {(pagination.last_page > 1 || pagination.page > 1) && (
                    <nav
                        aria-label={copy.pagination}
                        className="flex flex-wrap items-center justify-end gap-2 border-t border-border px-4 py-4 sm:px-6"
                    >
                        {[
                            {
                                label: copy.previous,
                                page: Math.min(
                                    pagination.last_page,
                                    pagination.page - 1,
                                ),
                                enabled: pagination.page > 1,
                                Icon: ArrowLeft,
                            },
                            {
                                label: copy.next,
                                page: pagination.page + 1,
                                enabled: pagination.page < pagination.last_page,
                                Icon: ArrowRight,
                            },
                        ].map(({ label, page, enabled, Icon }) => (
                            <Button
                                key={label}
                                asChild={enabled}
                                disabled={!enabled}
                                variant="outline"
                                size="sm"
                            >
                                {enabled ? (
                                    <Link
                                        href={index({
                                            query: { ...scope, page },
                                        })}
                                    >
                                        <Icon aria-hidden="true" />
                                        {label}
                                    </Link>
                                ) : (
                                    <>
                                        <Icon aria-hidden="true" />
                                        {label}
                                    </>
                                )}
                            </Button>
                        ))}
                    </nav>
                )}
            </section>
        </PublicLayout>
    );
}
