#!/usr/bin/php
<?php
require $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

use datagutten\AudioMetadata\AudioMetadata;
use datagutten\descriptionMaker\DescriptionMakers;
use datagutten\descriptionMaker\MusicBrainzDescription;
use datagutten\tools\files\files;
use GetOpt\GetOpt;
use torrentupload\sites\GazelleAPIUpload;
use torrentupload\upload;


$options = [['s', 'site', GetOpt::REQUIRED_ARGUMENT, 'Site name'],
    ['p', 'path', GetOpt::REQUIRED_ARGUMENT, 'File or folder to be uploaded'],
    ['m', 'mbid', GetOpt::OPTIONAL_ARGUMENT, 'MBID of the album to be uploaded'],
];

$upload = new upload($options);
$upload->login();
$pathinfo = $upload->pathinfo();


$mb = new MusicBrainzDescription();
if (empty($upload->getOpt->getOption('mbid')))
{
    try
    {
        $file = files::first_file($upload->getOpt->getOption('path'), ['flac']);
        $metadata = AudioMetadata::read_metadata($file);
        $mbid = $metadata['MUSICBRAINZ_ALBUMID'];
    }
    catch (FileNotFoundException $e)
    {
        die($e->getMessage());
    }
}
else
    $mbid = $upload->getOpt->getOption('mbid');

$release = $mb->releaseFromMBID($mbid, ['artists', 'labels', 'release-groups', 'media', 'recordings']);

$is_gazelle = is_a($upload->site, GazelleAPIUpload::class);
$description = $mb->build_description($mbid, $release, !$is_gazelle);
if (!$is_gazelle)
{
    $logs = $upload->log_files();
    $description .= DescriptionMakers::eac_log($logs[0]);
}

$upload->site->upload_mb(realpath($upload->torrent_file()), $release, $description, ['norwegian'], true, logs: $upload->log_files());
