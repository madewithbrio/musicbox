<?php

class Sapo_Share_Facebook {
	public static function shareArticle($article, $appendTitle = "") {
		if ($article === null) return;
		if ($article instanceof Sapo_DataTypes_Element_Share) {
			$title = $article->getTitle() . $appendTitle;
			$description = $article->getLead();
			$image = $article->getImage();
			$meta = new Sapo_Share_Facebook_Obj($title, $description, $image);
			Sapo_Controller::getEngineInstance()->setMetaData($meta);
		}
	}
}

class Sapo_Share_Facebook_Obj {
	public $title;
	public $description;
	public $url;
	public $image;

	public function __construct($title, $description, $image) {
		$this->title = preg_replace('@\n@', '', $title);
		$this->description = preg_replace('@\n@', '', $description);
		$this->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$this->image = Sapo_Thumbs::generateThumbUrl($image, 200);
	}

	public function getTitle() { return $this->title; }
	public function getDescription() { return $this->description; }
	public function getUrl() { return $this->url; }
	public function getImage() { return $this->image; }
}