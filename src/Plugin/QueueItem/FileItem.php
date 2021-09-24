<?php

namespace Drupal\quant\Plugin\QueueItem;

use Drupal\quant\Event\QuantFileEvent;

/**
 * A quant queue file item.
 *
 * @ingroup quant
 */
class FileItem implements QuantQueueItemInterface {

  /**
   * A filepath.
   *
   * @var string
   */
  private $file;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $data = []) {
    $this->file = $data['file'];
    $this->url = isset($data['url']) ? $data['url'] : NULL;
    $this->fullPath = isset($data['full_path']) ? $data['full_path'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    if ($this->url && strpos($this->url, '/styles/') > -1 && $this->fullPath) {
      // Generate an image style file on disk.
      $config = \Drupal::config('quant.settings');
      $local_host = $config->get('local_server') ?: 'http://localhost';
      $hostname = $config->get('host_domain') ?: $_SERVER['SERVER_NAME'];
      $image_style_url = $local_host . $this->fullPath;

      $headers['Host'] = $hostname;
      $auth = !empty($_SERVER['PHP_AUTH_USER']) ? [
        $_SERVER['PHP_AUTH_USER'],
        $_SERVER['PHP_AUTH_PW'],
      ] : [];

      \Drupal::httpClient()->get($image_style_url, [
        'http_errors' => FALSE,
        'headers' => $headers,
        'auth' => $auth,
        'allow_redirects' => FALSE,
        'verify' => $config->get('ssl_cert_verify'),
      ]);
    }

    if (file_exists(DRUPAL_ROOT . $this->file)) {
      \Drupal::service('event_dispatcher')->dispatch(QuantFileEvent::OUTPUT, new QuantFileEvent(DRUPAL_ROOT . $this->file, $this->file));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function info() {
    return ['#type' => 'markup', '#markup' => '<b>File: </b>' . $this->file];
  }

  /**
   * {@inheritdoc}
   */
  public function log() {
    return '[file_item]: ' . $this->file;
  }

}