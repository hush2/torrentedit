<?php
// (C) 2012 hush2 <hushywushy@gmail.com>

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Guzzle\Http\Exception\CurlException,
    Guzzle\Http\Exception\BadResponseException,
    TorrentEdit\Torrent,
    TorrentEdit\TorrentException;

$app = new Silex\Application();
$app['debug']= $_SERVER['HTTP_HOST'] == 'localhost';

// Enable Twig.
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFunction('format_filesize', new Twig_Function_Function('format_filesize'));
    return $twig;
}));


// Cut and pasted from Silex manual :)
$app->error(function (\Exception $e, $code) {
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }
    return new Response($message);
});

// INDEX page.
$app->get('/', function() use ($app) {
    return $app['twig']->render('index.twig');
});


// ------------------------------------------------------
$app->post('/edit', function(Request $request) use ($app) {

    $url = $request->get('url');
    $file = $request->files->get('file');

    if (empty($url) && !is_object($file)) {
        $data['error'] = 'Nothing to do! :-)';
        return $app['twig']->render('index.twig', $data);
    }

    $file = $url ? fetch($url) : open($file);

    if (!$file || is_string($file)) {
        $data['url'] = $url;
        $data['error'] = $file;
        return $app['twig']->render('index.twig', $data);
    }

    try {
        $torrent = new Torrent($file['content']);
    } catch (TorrentException $e) {
        $data['error'] = $e->getMessage();
        return $app['twig']->render('index.twig', $data);
    }

    $data['filename']       = $file['filename'];
    $data['trackers']       = $torrent->trackers;
    $data['creation_date']  = $torrent->creation_date;
    $data['created_by']     = $torrent->created_by;
    $data['comment']        = $torrent->comment;
    $data['encoding']       = $torrent->encoding;
    $data['private']        = $torrent->private;
    $data['hash']           = $torrent->info_hash;
    $data['pieces']         = $torrent->pieces;
    $data['piece_length']   = format_filesize($torrent->piece_length);
    $data['dir']            = $torrent->dir;
    $data['files']          = $torrent->files;
    $data['total_files']    = count($torrent->files);
    $data['raw']            = base64_encode($file['content']);

    return $app['twig']->render('edit.twig', $data);

});

// ------------------------------------------------------
$app->post('/save', function(Request $request) use ($app) {

    $content = base64_decode($request->get('raw'));
    try {
        $torrent = new Torrent($content);
    } catch (BEncoderException $e) {
        $app->abort(500, 'Error processing file.');
    }

    $creation_date = $request->get('creation_date');
    if ($creation_date) {
        $torrent->creation_date  = $creation_date;
    }
    $created_by = $request->get('created_by');
    if ($created_by) {
        $torrent->created_by  = $created_by;
    }
    $comment = $request->get('comment');
    if ($comment) {
        $torrent->comment  = $comment;
    }
    $trackers = $request->get('trackers');
    if ($trackers) {
        $torrent->trackers  = $trackers;
    }

    $response = new Response($torrent->save());
    $response->headers->set('Content-Type', 'application/x-bittorrent');
    $response->headers->set('Content-Transfer-Encoding', 'binary');
    $response->headers->set('Content-Disposition', 'inline; filename='.$request->get('filename'));
    //$response->headers->set('X-Powered-By', 'PHP & hush2');

    return $response;
});

// AJAX Request
$app->post('/scrape', function(Request $request) use ($app) {

    require __DIR__.'/../vendor/PHP-Torrent-Scraper/httptscraper.php';
    require __DIR__.'/../vendor/PHP-Torrent-Scraper/udptscraper.php';

    $url = $request->get('url');
    $info_hash = $request->get('info_hash');
    $timeout = 10;   // secs

    try {
        if (strpos($url, 'udp://') !== false) {
            $ts = new udptscraper($timeout);
        } else {
            $ts = new httptscraper($timeout);
        }
        echo json_encode($ts->scrape($url, $info_hash));

    } catch (ScraperException $e){
        echo json_encode(array($info_hash => false));
    }
});

//\\//\\//\\//
$app->run();
//\\//\\//\\//

function fetch($url)
{
    $data = array();
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $client = new Guzzle\Http\Client();
        $request = $client->get($url);
        try {
            $response = $request->send();
        } catch (CurlException $e) {
            return $e->getError();
        } catch (BadResponseException $e) {   // 4xx/5xx
            $response = $e->getResponse();
            return $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        }
        if (!$response->isContentType('application/x-bittorrent') &&
            !$response->isContentType('application/octet-stream')) {
            return 'URL is not a torrent.';
        }
        // Get 'original' filename.
        preg_match('/.*filename=["|\'](.*)["|\']/i', $response->getHeader('Content-Disposition'), $matches);
        if (count($matches) > 1) {
            $data['filename'] = $matches[1];
        } else {
            // Get filename from URL instead.
            $filename = explode('/', $url);
            $data['filename'] = end($filename);
        }
        $body = $response->getBody();
        $body->seek(0);
        $data['content'] = $body->read($body->getSize());
        return $data;
    }
    return 'Not a valid URL.';
}

// ------------------------------------------------------
function open($file)
{
    if (!$file->isValid()) {
        return $file->getError() . ' Upload Error.';
    }
    if ($file->getClientMimeType() != 'application/x-bittorrent') {
        return 'File is not a torrent.';
    }
    $data['filename'] = $file->getClientOriginalName();
    $data['content'] = file_get_contents($_FILES['file']['tmp_name']);
    return $data;

}

// http://php.net/manual/en/function.filesize.php
function format_filesize($size) {
    $sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]
                 : '0 Bytes';
}
