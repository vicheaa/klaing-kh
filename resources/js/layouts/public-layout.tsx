import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { index as administrativeAreas } from '@/routes/administrative-areas';
import { index as exchangeRates } from '@/routes/exchange-rates';

export const publicBrand = 'Klaing KH';

export type PublicLanguage = 'en' | 'km';
type PublicSection = 'home' | 'rates' | 'areas';

const labels = {
    en: {
        home: 'Home',
        rates: 'Exchange rates',
        areas: 'Administrative areas',
        navigation: 'Main navigation',
        switchLanguage: 'Switch to Khmer',
        about: 'An independent public data portal for Cambodia.',
        dashboard: 'Dashboard',
        login: 'Log in',
        register: 'Register',
        skip: 'Skip to content',
    },
    km: {
        home: 'ទំព័រដើម',
        rates: 'អត្រាប្តូរប្រាក់',
        areas: 'តំបន់រដ្ឋបាល',
        navigation: 'ម៉ឺនុយចម្បង',
        switchLanguage: 'ប្តូរទៅភាសាអង់គ្លេស',
        about: 'វេទិកាទិន្នន័យសាធារណៈឯករាជ្យសម្រាប់កម្ពុជា។',
        dashboard: 'ផ្ទាំងគ្រប់គ្រង',
        login: 'ចូលគណនី',
        register: 'ចុះឈ្មោះ',
        skip: 'រំលងទៅមាតិកា',
    },
};

export default function PublicLayout({
    children,
    title,
    language,
    section,
    languageHref,
}: {
    children: ReactNode;
    title: string;
    language: PublicLanguage;
    section: PublicSection;
    languageHref: NonNullable<InertiaLinkProps['href']>;
}) {
    const copy = labels[language];
    const alternate = language === 'en' ? 'km' : 'en';
    const flag = alternate === 'km' ? 'kh' : 'gb';
    const query = { lang: language };
    const navigation = [
        { section: 'home', href: home({ query }) },
        { section: 'rates', href: exchangeRates({ query }) },
        {
            section: 'areas',
            href: administrativeAreas({ query }),
        },
    ] as const;

    return (
        <div
            lang={language}
            className="public-theme flex min-h-screen flex-col bg-public-canvas text-foreground"
        >
            <Head title={title} />
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-3 focus:rounded-md focus:bg-background focus:px-4 focus:py-2 focus:text-foreground"
            >
                {copy.skip}
            </a>
            <header className="border-b border-border bg-background">
                <nav
                    aria-label={copy.navigation}
                    className="mx-auto flex max-w-7xl flex-wrap items-center gap-x-5 px-5 pt-3 pb-2 sm:px-8 md:gap-x-9 md:py-4"
                >
                    <Link
                        href={home({ query })}
                        className="flex min-h-10 shrink-0 items-center gap-2.5 rounded-lg font-semibold focus-visible:outline-2 focus-visible:outline-offset-4"
                    >
                        {/* <span className="grid size-9 place-items-center rounded-xl bg-primary text-primary-foreground">
                            <Database aria-hidden="true" className="size-4.5" />
                        </span> */}
                        <span className="text-lg tracking-tight" lang="en">
                            {publicBrand}
                        </span>
                    </Link>
                    <div className="order-last grid basis-full grid-cols-[auto_minmax(0,1fr)_minmax(0,1.25fr)] gap-1 pt-2 text-sm md:order-none md:flex md:basis-auto md:gap-x-1.5 md:pt-0">
                        {navigation.map((item) => (
                            <Link
                                key={item.section}
                                href={item.href}
                                aria-current={
                                    item.section === section
                                        ? 'page'
                                        : undefined
                                }
                                className={cn(
                                    'grid min-w-0 items-center rounded-lg px-1.5 py-2 text-center leading-snug font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 sm:px-3.5 md:text-left',
                                    item.section === section
                                        ? 'bg-accent text-accent-foreground'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                )}
                            >
                                <LanguageText
                                    language={language}
                                    en={labels.en[item.section]}
                                    km={labels.km[item.section]}
                                />
                            </Link>
                        ))}
                    </div>
                    <Button
                        asChild
                        variant="outline"
                        size="icon"
                        className="ml-auto size-10 rounded-lg"
                    >
                        <Link
                            href={languageHref}
                            hrefLang={alternate}
                            aria-label={copy.switchLanguage}
                            preserveState
                            preserveScroll
                        >
                            <img
                                src={`/assets/flag-${flag}.png`}
                                alt=""
                                width={24}
                                height={16}
                                className="object-contain"
                            />
                        </Link>
                    </Button>
                </nav>
            </header>

            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto grid w-full max-w-7xl grow content-start gap-6 px-5 py-7 sm:px-8 sm:py-9"
            >
                {children}
            </main>

            {/* <footer className="border-t border-border bg-background">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-7 gap-y-3 px-5 py-5 text-sm text-muted-foreground sm:px-8">
                    <p>
                        <span
                            lang="en"
                            className="font-semibold text-foreground"
                        >
                            {publicBrand}
                        </span>
                        <span aria-hidden="true"> · </span>
                        {copy.about}
                    </p>
                    <div className="flex flex-wrap gap-4 font-medium">
                        {auth.user ? (
                            currentTeam && (
                                <Link
                                    href={dashboard(currentTeam.slug)}
                                    className="hover:text-foreground hover:underline"
                                >
                                    {copy.dashboard}
                                </Link>
                            )
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="hover:text-foreground hover:underline"
                                >
                                    {copy.login}
                                </Link>
                                <Link
                                    href={register()}
                                    className="hover:text-foreground hover:underline"
                                >
                                    {copy.register}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </footer> */}
        </div>
    );
}

export function LanguageText({
    language,
    en,
    km,
}: {
    language: PublicLanguage;
    en: string;
    km: string;
}) {
    const alternate = language === 'en' ? 'km' : 'en';
    const text = { en, km };

    // Both translations size the same grid cell so switching cannot move its neighbours.
    return (
        <span className="inline-grid min-w-0 items-center">
            <span className="col-start-1 row-start-1">{text[language]}</span>
            <span
                lang={alternate}
                aria-hidden="true"
                className="invisible col-start-1 row-start-1 select-none"
            >
                {text[alternate]}
            </span>
        </span>
    );
}

export function PublicPageHeading({
    icon: Icon,
    title,
    description,
    children,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
    children?: ReactNode;
}) {
    return (
        <section className="grid gap-2 rounded-2xl border border-border bg-accent px-5 py-6 text-accent-foreground sm:px-8 sm:py-8">
            <div className="mb-1 flex items-center justify-between gap-3">
                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-background text-primary">
                    <Icon aria-hidden="true" className="size-5" />
                </span>
                {children}
            </div>
            <h1 className="text-2xl leading-tight font-semibold tracking-tight sm:text-4xl">
                {title}
            </h1>
            <p className="max-w-4xl leading-relaxed text-muted-foreground max-sm:min-h-[2lh]">
                {description}
            </p>
        </section>
    );
}
