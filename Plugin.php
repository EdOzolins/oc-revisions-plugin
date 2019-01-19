<?php namespace NedStrk\Revisions;

use Backend;
use Event;
use System\Classes\PluginBase;
use Cms\Classes\Theme;

/**
 * Revisions Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'CMS Page Revisions',
            'description' => 'View and restore cms page revision',
            'author'      => 'NedStrk',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        Event::listen('backend.form.extendFields', function ($widget) {

            ### don't add a revisions tab if the log is disabled
            if (\System\Models\LogSetting::instance()->log_theme == 0) {
                return;
            }

            if (!$widget->model instanceof \Cms\Classes\Page) {
                return;
            }

            if (!($theme = Theme::getEditTheme())) {
                throw new ApplicationException(Lang::get('cms::lang.theme.edit.not_found'));
            }

            if (!$widget->isNested) {
                $widget->addFields(
                     [
                         'revisions' => [
                             'type'    => 'partial',
                             'tab'     => 'Revisions',
                             'span'    => 'full',
                             'stretch' => true,
                             'path'    => '$/nedstrk/revisions/partials/_revisions_table.htm'
                         ],
                     ],
                     'primary'
                 );
            }
        });
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        \Cms\Controllers\ThemeLogs::extend(function ($controller) {
            $controller->addDynamicMethod('onRestore', function () use ($controller) {

                $revision = \Cms\Models\ThemeLog::findOrFail(input('id'));

                $file = themes_path($revision->theme . '/' . $revision->template);

                $filesystem = new \October\Rain\Filesystem\Filesystem;

                $filesystem->put($file, $revision->content);

                Event::fire('nedstrk.revision.restore', [$revision]);

                \Flash::success('Revision successfully restored!');

            });
        });

        ### get page revisions
        \Cms\Classes\Page::extend(function ($model) {
            $model->addDynamicMethod('revisions', function () use ($model) {
                $filename = 'pages/' . $model->fileName;
                return \Cms\Models\ThemeLog::where('template', $filename)->with('user')->orderBy('created_at', 'desc')->get();
            });
        });

        ### exclude the revision folder from the CMS/Page sidebar
        Event::listen('cms.object.listInTheme', function ($cmsObject, $objectList) {
            if ($cmsObject instanceof \Cms\Classes\Page) {
                foreach ($objectList as $index => $page) {
                    if (dirname($page->fileName) === 'revisions') {
                        $objectList->forget($index);
                    }
                }
            }
        });

        \Cms\Controllers\ThemeLogs::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof \Cms\Models\ThemeLog) {
                return;
            }

            if (!$model->exists) {
                return;
            }

            if ($form->getContext() != 'preview') {
                return;
            }

            if (dirname($model->template) != 'pages') {
                return;
            }

            $form->addTabFields([
                'restore' => [
                    'tab' => 'cms::lang.theme_log.diff',
                    'type' => 'partial',
                    'path' => '$/nedstrk/revisions/partials/_restore_field.htm'
                ],
            ]);
        });
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {

        return [];

        return [
            'nedstrk.revisions.view' => [
                'tab' => 'Revisions',
                'label' => 'View CMS page revisions'
            ],
        ];
    }
}
