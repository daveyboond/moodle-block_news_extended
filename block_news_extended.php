<?php

class block_news_extended extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_news_extended');
    }

    function applicable_formats() {
        return array('my' => true);
    }

    function get_content() {
        global $CFG, $USER;

        /* Same as block_news_items to start with */

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        /* Code hereafter cannibalised from /moodle/index.html Site News section */

        require_once($CFG->dirroot.'/mod/forum/lib.php');

        // Check news forum exists in this course
        if (! $newsforum = forum_get_course_forum($this->page->course->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        // Fetch news forum context for proper filtering to happen
        $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $this->page->course->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id);

        // Get name of forum
        $forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));
        $this->content->text .= html_writer::tag('a', get_string('skipa', 'access',
	    core_text::strtolower(strip_tags($forumname))), array('href'=>'#skipsitenews', 'class'=>'skip-block'));

        // Add subscribe/unsubscribe links
        if (isloggedin()) {
            $subtext = '';
            if (forum_is_subscribed($USER->id, $newsforum)) {
                if (!forum_is_forcesubscribed($newsforum)) {
                    $subtext = get_string('unsubscribe', 'forum');
                }
            } else {
                $subtext = get_string('subscribe', 'forum');
            }
            $suburl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id, 'sesskey' => sesskey()));
            $this->content->text .= html_writer::tag('div', html_writer::link($suburl, $subtext),
	        array('class' => 'subscribelink'));
        }

        // Use output buffering to capture output from forum listing function
        ob_start();
        forum_print_latest_discussions($this->page->course, $newsforum, $this->page->course->newsitems, 'plain',
		    'p.modified DESC');
        $this->content->text .= ob_get_contents();
        ob_end_clean();
		
	// Enlarge the user pictures (strange combination of CSS affecting image size here.
	// This is best I can do - sizes above 35px get cropped)
	if (preg_match_all('/<img.*?class=".*?userpicture.*?".*?>/', $this->content->text, $matches)) {
            foreach ($matches[0] as $match) {
                $imgelement = preg_replace('/width=".*?"/', 'style="width:35px;height:35px"', $match);
                $imgelement = preg_replace('/height=".*?"/', '', $imgelement);
		    $this->content->text = str_replace($match, $imgelement, $this->content->text);
            }
        }

	return $this->content;
    }
}


