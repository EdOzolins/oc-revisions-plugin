<?php
Route::group(['middleware' => ['web', 'NedStrk\Revisions\Classes\RevisionsMiddleware'], 'prefix' => 'revision'], function() {

  Route::get('cms/page/{id}', function($id) {

      $revision = \Cms\Models\ThemeLog::findOrFail($id);

      //$theme = \Cms\Classes\Theme::getActiveThemeCode();
      $theme = $revision->theme;

      $path = themes_path($theme . '/pages/revisions');

      $filesystem = new \October\Rain\Filesystem\Filesystem;

      ### Create folder if doesn't exist
      if (!file_exists($path)) {
        $filesystem->makeDirectory($path);
      }

      $file =  $path . '/' . $id . '.htm';

      ### create file and put content in it
      if (!file_exists($file)) {
         $filesystem->put($file, $revision->content);
      }

      $page = \Cms\Classes\Page::loadCached($theme, 'revisions/' . $id . '.htm');

      return App::make(\Cms\Classes\Controller::class)->runPage($page);

  });

});
