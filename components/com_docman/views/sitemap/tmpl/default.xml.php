<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
    <? foreach ($documents as $document): ?>
        <url>
            <loc><?= $document->title_link ?></loc>
            <lastmod><?= helper('date.format', array('date' => $document->modified_on, 'format' => translate('DATE_FORMAT_LC4'))); ?></lastmod>
            <? if ($document->isImage()): ?>
                <image:image>
                    <image:loc><?= $document->download_link ?></image:loc>
                    <image:title><?= $document->title ?></image:title>
                </image:image>
            <? elseif ($document->isVideo()): ?>
                <video:video>
                    <video:thumbnail_loc><?= $document->image_path ?></video:thumbnail_loc>
                    <video:title><?= $document->title ?></video:title>
                    <video:description><?= $document->description ?></video:description>
                    <video:content_loc><?= $document->download_link ?></video:content_loc>
                    <video:live>no</video:live>
                </video:video>
            <? endif ?>
        </url>
    <? endforeach; ?>
</urlset>