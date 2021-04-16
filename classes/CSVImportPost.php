<?php
/**
 * A helper class for insert post data.
 */

class CSVImportPost {
	/**
	 * @var $post WP_Post object
	 */
	private $post;

	/**
	 * @var $error WP_Error object
	 */
	private $error;

	/**
	 * Add an error or append additional message to this object.
	 *
	 * @param int|string $code Error code.
	 * @param string $message Error message.
	 * @param mixed $data Optional. Error data.
	 */
	public function addError( $code, $message, $data = '')
	{
		if (!$this->isError()) {
			$e = new WP_Error();
			$this->error = $e;
		}
		$this->error->add($code, $message, $data);
	}

	/**
	 * Get the error of this object
	 *
	 * @return WP_Error (WP_Error)
	 */
	public function getError(): WP_Error {
		if ( ! $this->isError() ) {
			$e = new WP_Error();

			return $e;
		}

		return $this->error;
	}

	/**
	 * Check the object has some Errors.
	 *
	 * @return bool (bool)
	 */
	public function isError() {
		return is_wp_error($this->error);
	}

	/**
	 * Set WP_Post object
	 *
	 * @param (int) $post_id Post ID
	 */
	protected function setPost($post_id)
	{
		$post = get_post($post_id);
		if (is_object($post)) {
			$this->post = $post;
		} else {
			$this->addError('post_id_not_found', __('Provided Post ID not found.', 'csv-create-posts'));
		}
	}

	/**
	 * Get WP_Post object
	 *
	 * @return (WP_Post|null)
	 */
	public function getPost()
	{
		return $this->post;
	}

	/**
	 * Add a post
	 *
	 * @param (array) $data An associative array of the post data
	 *
	 * @return CSVImportPost (CSVImportPost)
	 */
	public static function add($data)
	{
		$object = new CSVImportPost();
		$post_id = wp_insert_post($data, true);

		if (is_wp_error($post_id)) {
			$object->addError($post_id->get_error_code(), $post_id->get_error_message());
		} else {
			$object->setPost($post_id);
		}
		return $object;
	}

	/**
	 * A wrapper of wp_set_object_terms
	 *
	 * @param (array/string) $taxonomy The context in which to relate the term to the object
	 * @param (array/int/string) $terms The slug or id of the term
	 */
	public function setObjectTerms($taxonomy, $terms)
	{
		$post = $this->getPost();
		if ($post instanceof WP_Post) {
			wp_set_object_terms($post->ID, $terms, $taxonomy);
		} else {
			$this->addError('post_is_not_set', __('WP_Post object is not set.', 'csv-create-posts'));
		}
	}
}