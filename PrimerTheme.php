<?php

declare(strict_types=1);

namespace Primer;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Module\MinimalTheme;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PrimerTheme extends MinimalTheme implements ModuleCustomInterface, ModuleGlobalInterface, ModuleConfigInterface {
    use ModuleConfigTrait;
    use ModuleCustomTrait;
    use ModuleGlobalTrait;

    public const CUSTOM_AUTHOR = 'Fredrik Ekdahl';
    public const CUSTOM_VERSION = '1.2.0';
    public const GITHUB_REPO = 'ekdahl/webtrees-primer-theme';
    public const CUSTOM_SUPPORT_URL = 'https://github.com/ekdahl/webtrees-primer-theme';
    public const CUSTOM_LATEST_VERSION = 'https://raw.githubusercontent.com/' . self::GITHUB_REPO . '/main/latest-version.txt';

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Primer';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_SUPPORT_URL;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleLatestVersionUrl()
     */
    public function customModuleLatestVersionUrl(): string
    {
        return self::CUSTOM_LATEST_VERSION;
    }

    /**
     * Generate a list of items for the user menu.
     *
     * @param Tree|null $tree
     *
     * @return array<Menu>
     */
    public function userMenu(?Tree $tree): array
    {
        return array_filter([
            $this->menuPendingChanges($tree),
            $this->menuMyPages($tree),
            $this->menuThemes(),
            $this->menuPalette(),
            $this->menuLanguages(),
            $this->menuLogin(),
            $this->menuLogout(),
        ]);
    }

    /**
     * Bootstrap the module
     */
    public function boot(): void
    {
        // Register a namespace for our views.
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
    }

    /**
     * Where does this module store its resources
     *
     * @return string
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

    /**
     * Raw content, to be added at the end of the <head> element.
     * Typically, this will be <link> and <meta> elements.
     * we use it to load the special font files
     *
     * @return string
     */
    public function headContent(): string
    {
        return
            '<style>
            @font-face {
                font-family: \'Font Awesome 6 Free\';
                src: url("' . $this->assetUrl('fonts/fa-solid-900.woff2') . '") format("woff2");
                font-style: normal;
                font-weight: 900;
                font-display: block;
            }
            @font-face {
                font-family: \'icomoon\';
                src: url("' . $this->assetUrl('fonts/icomoon.woff') . '") format("woff");
                font-style: normal;
                font-weight: normal;
                font-display: block;
            }
            </style>';
    }

    /**
     * Add our own stylesheet to the existing stylesheets.
     *
     * @return array<string>
     */
    public function stylesheets(): array
    {
        $stylesheets = parent::stylesheets();

        $stylesheets[] = $this->assetUrl('css/theme.css');
        $stylesheets[] = $this->assetUrl('css/charts.css');
        $stylesheets[] = $this->assetUrl('css/icons.css');
        $stylesheets[] = $this->assetUrl('css/primer/base/size/size.css');
        $stylesheets[] = $this->assetUrl('css/primer/base/typography/typography.css');
        $stylesheets[] = $this->assetUrl('css/primer/functional/size/border.css');
        $stylesheets[] = $this->assetUrl('css/primer/functional/size/size.css');
        $stylesheets[] = $this->assetUrl('css/primer/functional/themes/' . $this->palette() . '.css');
        $stylesheets[] = $this->assetUrl('css/primer/functional/typography/typography.css');
        $stylesheets[] = $this->assetUrl('css/primer/primitives/temp-typography-tokens.css');
        $stylesheets[] = $this->assetUrl('css/site-logo-' . $this->getPreference('logo', 'people') . '.css');

        return $stylesheets;
    }

    /**
     * Create a menu of palette options
     *
     * @return Menu
     */
    protected function menuPalette(): Menu
    {
        /* I18N: A colour scheme */
        $menu = new Menu(I18N::translate('Palette'), '#', 'menu-color');

        $palette = $this->palette();

        foreach ($this->palettes() as $palette_id => $palette_name) {
            $url = route('module', ['module' => $this->name(), 'action' => 'Palette', 'palette' => $palette_id]);

            $submenu = new Menu(
                $palette_name,
                '#',
                'menu-primer-' . $palette_id . ($palette === $palette_id ? ' active' : ''),
                [
                    'data-wt-post-url' => $url,
                ]
            );

            $menu->addSubmenu($submenu);
        }

        return $menu;
    }

    /**
     * Switch to a new palette
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postPaletteAction(ServerRequestInterface $request): ResponseInterface
    {
        $user    = Validator::attributes($request)->user();
        $palette = Validator::queryParams($request)->isInArrayKeys($this->palettes())->string('palette');

        $user->setPreference('primer-palette', $palette);

        Session::put('primer-palette', $palette);

        return response();
    }

     /**
     * @return array<string>
     */
    private function palettes(): array
    {
        $palettes = [
            'dark'                => I18N::translate('Dark default'),
            'dark-dimmed'         => I18N::translate('Dark dimmed'),
            'dark-high-contrast'  => I18N::translate('Dark high contrast'),
            'light'               => I18N::translate('Light default'),
            'light-high-contrast' => I18N::translate('Light high contrast'),
        ];

        return $palettes;
    }

    /**
     * @return string
     */
    private function palette(): string
    {
        // If we are logged in, use our preference
        $palette = Auth::user()->getPreference('primer-palette');

        // If not logged in or no preference, use one we selected earlier in the session.
        if ($palette === '') {
            $palette = Session::get('primer-palette');
            $palette = is_string($palette) ? $palette : '';
        }

        // We haven't selected one this session? Use the default
        if ($palette === '') {
            $palette = $this->getPreference('palette', 'dark');
        }

        return $palette;
    }

     /**
     * @return array<string>
     */
    private function logos(): array
    {
        $logos = [
            'people'   => I18N::translate('People'),
            'webtrees' => I18N::translate('webtrees'),
        ];

        return $logos;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function getAdminAction(): ResponseInterface
    {
        if (Session::get('theme') !== $this->name()) {
            // We need to register the namespace for this view because the boot didn't run
            View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
        }

        $this->layout = 'layouts/administration';

        return $this->viewResponse($this->name() . '::settings', [
            'palette'      => $this->getPreference('palette', 'dark'),
            'palettes'     => $this->palettes(),
            'title'        => $this->title(),
            'logo'    => $this->getPreference('logo', 'people'),
            'logos'   => $this->logos(),
        ]);
    }

    /**
     * Save the user preference.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();

        if ($params['save'] === '1') {
            $this->setPreference('palette', $params['palette']);
            $this->setPreference('logo', $params['logo']);
            $message = I18N::translate('The preferences for the module “%s” have been updated.', $this->title());
            FlashMessages::addMessage($message, 'success');
        }

        return redirect($this->getConfigLink());
    }
};
