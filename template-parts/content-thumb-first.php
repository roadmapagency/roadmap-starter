<?php
\RoadmapStarter\Blocks\ImageAndText\ImageAndText::display(
	array(
		'title'               => get_the_title(),
		'description'         => '<p>' . get_the_excerpt() . '</p>',
		'image'               => array( 'ID' => get_post_thumbnail_id() ),
		'theme'               => 'green',
		'remove_angles'       => 1,
		'primary_button_text' => 'Read More',
		'primary_button_url'  => get_the_permalink(),
	)
);

get_search_form();
