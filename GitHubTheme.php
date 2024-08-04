<?php

declare(strict_types=1);

namespace GitHub;

use Fisharebest\Webtrees\Module\MinimalTheme;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;

class GitHubTheme extends MinimalTheme implements ModuleCustomInterface, ModuleGlobalInterface {
    use ModuleCustomTrait;
    use ModuleGlobalTrait;

    /**
     * @return string
     */
    public function title(): string
    {
        return 'GitHub';
    }

    /**
     * Bootstrap the module
     */
    public function boot(): void
    {
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

        // NOTE - a future version of webtrees will allow the modules to be stored in a private folder.
        // Only files in the /public/ folder will be accessible via the webserver.
        // Since modules cannot copy their files to the /public/ folder, they need to provide them via a callback.
        $stylesheets[] = $this->assetUrl('css/theme.css');

        return $stylesheets;
    }
};