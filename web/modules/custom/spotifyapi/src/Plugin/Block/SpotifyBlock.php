<?php

namespace Drupal\spotifyapi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotifyapi\SpotifyApi;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Artists list' Block with data sourced from Spotify.
 *
 * @Block(
 *   id = "spotify_artist_list",
 *   admin_label = @Translation("Artists List"),
 * )
 */
class SpotifyBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\spotifyapi\SpotifyApi */
  protected $spotifyApi;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SpotifyApi $spotifyApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->spotifyApi = $spotifyApi;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('spotifyapi.spotifyapi')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['artist_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Artists IDs to get, up to 20, divided by comma'),
      '#required' => TRUE,
      '#default_value' => isset($config['artist_id']) ? $config['artist_id'] : '',
    ];

    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state) {
      parent::blockValidate($form, $form_state);
      $artists_array = explode(',', $form_state->getValue('artist_id'));

      if(!preg_match_all(
          "/^[a-zA-Z0-9,]*$/",
          $form_state->getValue('artist_id')
      )) {
          // Set an error for the form element with a key of "Artist ID".
          $form_state->setError($form['artist_id'], $this->t('Please put artists ids divided by comma, without spaces, newlines or other characters'));
      }

      if (count($artists_array) > 20) {
          // Set an error for the form element with a key of "Artist ID".
          $form_state->setError($form['artist_id'], $this->t('The amount of artists should not exceed 20.'));
      }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['artist_id'] = $values['artist_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Starting point to get further artists
    $config = $this->getConfiguration();
    $artists = $this->spotifyApi->getArtists(trim($config['artist_id']));

    return [
      '#theme' => 'artist_list',
      '#artists' => $artists['artists']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 60 * 60 * 24; // 1 day
  }

}
