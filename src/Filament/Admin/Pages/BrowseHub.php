<?php

declare(strict_types=1);

namespace Simplify\Plugins\Filament\Admin\Pages;

use App\Models\Plugin;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;
use Simplify\Plugins\Services\HubApi;
use Simplify\Plugins\Services\HubCatalog;
use Simplify\Plugins\Services\HubInstaller;
use Throwable;

/**
 * The themes, plugins and languages on the Pelican Hub, with their makers up front.
 */
final class BrowseHub extends Page
{
    protected static ?string $slug = 'browse-hub';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-world-search';

    protected static ?int $navigationSort = 99;

    protected string $view = 'simplify-plugins::browse-hub';

    #[Url]
    public string $type = 'theme';

    #[Url]
    public string $maker = '';

    #[Url(as: 'q')]
    public string $search = '';

    /** The page draws at once; the Hub is read right after, so a slow Hub never holds up the panel. */
    public bool $loaded = false;

    public static function canAccess(): bool
    {
        return (bool) user()?->can('viewAny', Plugin::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('simplify-plugins::plugins.title');
    }

    public function getTitle(): string
    {
        return __('simplify-plugins::plugins.title');
    }

    public function getSubheading(): string
    {
        return __('simplify-plugins::plugins.subheading');
    }

    public function load(): void
    {
        $this->loaded = true;
    }

    public function updatedType(): void
    {
        $this->maker = '';
    }

    /** @return array{items: list<array<string, mixed>>, makers: list<array<string, mixed>>, counts: array<string, int>, error: bool} */
    public function hub(): array
    {
        $catalog = HubCatalog::catalog();
        $type = in_array($this->type, HubCatalog::TYPES, true) ? $this->type : 'theme';
        $installed = Plugin::query()->pluck('id')->flip();
        $search = mb_strtolower(trim($this->search));

        $items = array_values(array_filter($catalog['items'], fn (array $item) => $item['type'] === $type
            && ($this->maker === '' || $item['author'] === $this->maker)
            && ($search === '' || str_contains(mb_strtolower($item['title'].' '.$item['author'].' '.$item['summary']), $search))));
        $items = array_map(fn (array $item) => [...$item, 'installed' => $installed->has($item['slug'])], $items);

        $counts = array_fill_keys(HubCatalog::TYPES, 0);
        foreach ($catalog['items'] as $item) {
            $counts[$item['type']]++;
        }

        return [
            'items' => $items,
            'makers' => HubCatalog::makers($catalog['items'], $type),
            'counts' => $counts,
            'error' => $catalog['error'],
        ];
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label(__('simplify-plugins::plugins.details'))
            ->color('gray')
            ->button()
            ->size('sm')
            ->modalHeading(fn (array $arguments) => HubCatalog::details((string) ($arguments['slug'] ?? ''))['title'] ?? '')
            ->modalWidth(Width::FourExtraLarge)
            ->modalContent(fn (array $arguments) => view('simplify-plugins::details', [
                'item' => HubCatalog::details((string) ($arguments['slug'] ?? '')),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('simplify-plugins::plugins.close'))
            ->extraModalFooterActions(fn (array $arguments) => [
                Action::make('openOnHub')
                    ->label(__('simplify-plugins::plugins.view_on_hub'))
                    ->icon('tabler-external-link')
                    ->url(HubApi::HUB.'/plugins/'.($arguments['slug'] ?? ''), shouldOpenInNewTab: true),
            ]);
    }

    public function canInstall(): bool
    {
        return (bool) user()?->can('create', Plugin::class) && HubInstaller::supported();
    }

    public function installAction(): Action
    {
        $item = fn (array $arguments) => collect(HubCatalog::catalog()['items'])->firstWhere('slug', (string) ($arguments['slug'] ?? ''));
        $blocked = fn (array $arguments) => ($item($arguments)['type'] ?? '') === 'theme' && ! HubInstaller::canBuildThemes();

        return Action::make('install')
            ->label(__('simplify-plugins::plugins.install.button'))
            ->icon('tabler-download')
            ->button()
            ->size('sm')
            ->authorize(fn () => $this->canInstall())
            ->requiresConfirmation()
            ->modalIcon('tabler-download')
            ->modalHeading(fn (array $arguments) => __('simplify-plugins::plugins.install.heading', ['name' => $item($arguments)['title'] ?? '']))
            ->modalDescription(fn (array $arguments) => $blocked($arguments)
                ? __('simplify-plugins::plugins.install.needs_yarn')
                : __('simplify-plugins::plugins.install.confirm', ['name' => $item($arguments)['author'] ?? '']))
            ->modalSubmitActionLabel(__('simplify-plugins::plugins.install.button'))
            ->modalSubmitAction(fn (Action $action, array $arguments) => $action->disabled($blocked($arguments)))
            ->action(function (array $arguments): void {
                try {
                    HubInstaller::install((string) ($arguments['slug'] ?? ''), user());
                    Notification::make()
                        ->title(__('simplify-plugins::plugins.install.started'))
                        ->body(__('simplify-plugins::plugins.install.started_body'))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);
                    Notification::make()
                        ->title(__('simplify-plugins::plugins.install.failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('simplify-plugins::plugins.refresh'))
                ->icon('tabler-refresh')
                ->color('gray')
                ->action(function (): void {
                    // At most one fresh read per five minutes, however often the button is pressed.
                    $at = HubCatalog::catalog()['at'];
                    if (is_int($at) && time() - $at < 300) {
                        Notification::make()->title(__('simplify-plugins::plugins.up_to_date'))->info()->send();

                        return;
                    }
                    HubCatalog::forget();
                    HubCatalog::refresh();
                }),
            Action::make('hub')
                ->label(__('simplify-plugins::plugins.open_hub'))
                ->icon('tabler-external-link')
                ->color('gray')
                ->url(HubApi::HUB, shouldOpenInNewTab: true),
        ];
    }
}
