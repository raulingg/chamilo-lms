<?php
/* For licensing terms, see /license.txt */
/**
 * BLOG HOMEPAGE
 * This file takes care of all blog navigation and displaying.
 * @package chamilo.blogs
 */
require_once __DIR__.'/../inc/global.inc.php';

$blog_id = intval($_GET['blog_id']);

if (empty($blog_id)) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_BLOGS;

/* 	ACCESS RIGHTS */
// notice for unauthorized people.
api_protect_course_script(true);

$lib_path = api_get_path(LIBRARY_PATH);
$blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

$nameTools = get_lang('Blogs');
$DaysShort = api_get_week_days_short();
$DaysLong = api_get_week_days_long();
$MonthsLong = api_get_months_long();

$action = isset($_GET['action']) ? $_GET['action'] : null;

/*
	PROCESSING
*/

$safe_post_file_comment = isset($_POST['post_file_comment']) ? Security::remove_XSS($_POST['post_file_comment']) : null;
$safe_comment_text = isset($_POST['comment_text']) ? Security::remove_XSS($_POST['comment_text']) : null;
$safe_comment_title = isset($_POST['comment_title']) ? Security::remove_XSS($_POST['comment_title']) : null;
$safe_task_name = isset($_POST['task_name']) ? Security::remove_XSS($_POST['task_name']) : null;
$safe_task_description = isset($_POST['task_description']) ? Security::remove_XSS($_POST['task_description']) : null;

if (!empty($_POST['new_post_submit'])) {
    Blog:: create_post(
        $_POST['title'],
        $_POST['full_text'],
        $_POST['post_file_comment'],
        $blog_id
    );
    Display::addFlash(
        Display::return_message(get_lang('BlogAdded'), 'success')
    );
}
if (!empty($_POST['edit_post_submit'])) {
    Blog:: edit_post(
        $_POST['post_id'],
        $_POST['title'],
        $_POST['full_text'],
        $blog_id
    );
    Display::addFlash(
        Display::return_message(get_lang('BlogEdited'), 'success')
    );
}

if (!empty($_POST['new_task_submit'])) {
    Blog:: create_task(
        $blog_id,
        $safe_task_name,
        $safe_task_description,
        (isset($_POST['chkArticleDelete']) ? $_POST['chkArticleDelete'] : null),
        (isset($_POST['chkArticleEdit']) ? $_POST['chkArticleEdit'] : null),
        (isset($_POST['chkCommentsDelete']) ? $_POST['chkCommentsDelete'] : null),
        (isset($_POST['task_color']) ? $_POST['task_color'] : null)
    );

    Display::addFlash(
        Display::return_message(get_lang('TaskCreated'), 'success')
    );
}

if (isset($_POST['edit_task_submit'])) {
    Blog:: edit_task(
        $_POST['blog_id'],
        $_POST['task_id'],
        $safe_task_name,
        $safe_task_description,
        $_POST['chkArticleDelete'],
        $_POST['chkArticleEdit'],
        $_POST['chkCommentsDelete'],
        $_POST['task_color']
    );
    Display::addFlash(
        Display::return_message(get_lang('TaskEdited'), 'success')
    );
}

if (!empty($_POST['assign_task_submit'])) {
    Blog:: assign_task(
        $blog_id,
        $_POST['task_user_id'],
        $_POST['task_task_id'],
        $_POST['task_day']
    );
    Display::addFlash(
        Display::return_message(get_lang('TaskAssigned'), 'success')
    );
}

if (isset($_POST['assign_task_edit_submit'])) {
    Blog:: edit_assigned_task(
        $blog_id,
        $_POST['task_user_id'],
        $_POST['task_task_id'],
        $_POST['task_day'],
        $_POST['old_user_id'],
        $_POST['old_task_id'],
        $_POST['old_target_date']
    );
    Display::addFlash(
        Display::return_message(get_lang('AssignedTaskEdited'), 'success')
    );
}
if (!empty($_POST['register'])) {
    if (is_array($_POST['user'])) {
        foreach ($_POST['user'] as $index => $user_id) {
            Blog:: set_user_subscribed((int) $_GET['blog_id'], $user_id);
        }
    }
}
if (!empty($_POST['unregister'])) {
    if (is_array($_POST['user'])) {
        foreach ($_POST['user'] as $index => $user_id) {
            Blog:: set_user_unsubscribed((int) $_GET['blog_id'], $user_id);
        }
    }
}
if (!empty($_GET['register'])) {
    Blog:: set_user_subscribed((int) $_GET['blog_id'], (int) $_GET['user_id']);
    Display::addFlash(
        Display::return_message(get_lang('UserRegistered'), 'success')
    );
    $flag = 1;
}
if (!empty($_GET['unregister'])) {
    Blog:: set_user_unsubscribed((int) $_GET['blog_id'], (int) $_GET['user_id']);
}

if (isset($_GET['action']) && $_GET['action'] == 'manage_tasks') {
    if (isset($_GET['do']) && $_GET['do'] == 'delete') {
        Blog:: delete_task($blog_id, (int) $_GET['task_id']);
        Display::addFlash(
            Display::return_message(get_lang('TaskDeleted'), 'success')
        );
    }

    if (isset($_GET['do']) && $_GET['do'] == 'delete_assignment') {
        Blog:: delete_assigned_task($blog_id, intval($_GET['task_id']), intval($_GET['user_id']));
        Display::addFlash(
            Display::return_message(get_lang('TaskAssignmentDeleted'), 'success')
        );
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'view_post') {
    $task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;

    if (isset($_GET['do']) && $_GET['do'] == 'delete_comment') {
        if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_delete', $task_id)) {
            Blog:: delete_comment($blog_id, (int) $_GET['post_id'], (int) $_GET['comment_id']);
            Display::addFlash(
                Display::return_message(get_lang('CommentDeleted'), 'success')
            );
        } else {
            Display::addFlash(
                Display::return_message(get_lang('ActionNotAllowed'), 'error')
            );
        }
    }

    if (isset($_GET['do']) && $_GET['do'] == 'delete_article') {
        if (api_is_allowed('BLOG_'.$blog_id, 'article_delete', $task_id)) {
            Blog:: delete_post($blog_id, (int) $_GET['article_id']);
            $action = ''; // Article is gone, go to blog home
            Display::addFlash(
                Display::return_message(get_lang('BlogDeleted'), 'success')
            );
        } else {
            Display::addFlash(
                Display::return_message(get_lang('ActionNotAllowed'), 'error')
            );
        }
    }
    if (isset($_GET['do']) && $_GET['do'] == 'rate') {
        if (isset($_GET['type']) && $_GET['type'] == 'post') {
            if (api_is_allowed('BLOG_'.$blog_id, 'article_rate')) {
                Blog:: add_rating('post', $blog_id, (int) $_GET['post_id'], (int) $_GET['rating']);
                Display::addFlash(
                    Display::return_message(get_lang('RatingAdded'), 'success')
                );
            }
        }
        if (isset($_GET['type']) && $_GET['type'] == 'comment') {
            if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_add')) {
                Blog:: add_rating('comment', $blog_id, (int) $_GET['comment_id'], (int) $_GET['rating']);
                Display::addFlash(
                    Display::return_message(get_lang('RatingAdded'), 'success')
                );
            }
        }
    }
}
/*
	DISPLAY
*/

// Set breadcrumb
switch ($action) {
    case 'new_post' :
        $nameTools = get_lang('NewPost');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            "name" => Blog:: get_blog_title($blog_id),
        );
        break;
    case 'view_post' :
        $nameTools = '';
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            "name" => Blog:: get_blog_title($blog_id),
        );
        break;
    case 'manage_tasks' :
        $nameTools = get_lang('TaskManager');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            "name" => Blog:: get_blog_title($blog_id),
        );
        break;
    case 'manage_members' :
        $nameTools = get_lang('MemberManager');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            "name" => Blog:: get_blog_title($blog_id),
        );
        break;
    case 'manage_rights' :
        $nameTools = get_lang('RightsManager');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            'name' => Blog:: get_blog_title($blog_id),
        );
        break;
    case 'view_search_result' :
        $nameTools = get_lang('SearchResults');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            'name' => Blog:: get_blog_title($blog_id),
        );
        break;
    case 'execute_task' :
        $nameTools = get_lang('ExecuteThisTask');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            'name' => Blog:: get_blog_title($blog_id),
        );
        break;
    default :
        $nameTools = Blog:: get_blog_title($blog_id);
}

$actionsLeft = [];
$actionsLeft[] = Display::url(
    Display::return_icon('blog.png', get_lang('Home'), '', ICON_SIZE_MEDIUM),
    api_get_self().'?blog_id='.$blog_id.'&'.api_get_cidreq()
);
if (api_is_allowed('BLOG_'.$blog_id, 'article_add')) {
    $actionsLeft[] = Display::url(
        Display::return_icon('new_article.png', get_lang('NewPost'), '', ICON_SIZE_MEDIUM),
        api_get_self().'?action=new_post&amp;blog_id='.$blog_id
    );
}
if (api_is_allowed('BLOG_'.$blog_id, 'task_management')) {
    $actionsLeft[] = Display::url(
        Display::return_icon('blog_tasks.png', get_lang('TaskManager'), '', ICON_SIZE_MEDIUM),
        api_get_self().'?action=manage_tasks&amp;blog_id='.$blog_id
    );
}
if (api_is_allowed('BLOG_'.$blog_id, 'member_management')) {
    $actionsLeft[] = Display::url(
        Display::return_icon('blog_admin_users.png', get_lang('MemberManager'), '', ICON_SIZE_MEDIUM),
        api_get_self().'?action=manage_members&amp;blog_id='.$blog_id
    );
}

$titleBlog = Blog::get_blog_title($blog_id);
$descriptionBlog = Blog::get_blog_subtitle($blog_id);
$idBlog = $blog_id;

$searchBlog = isset($_GET['q']) ? Security::remove_XSS($_GET['q']) : '';
//calendar blog
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$calendarBlog = Blog::display_minimonthcalendar($month, $year, $blog_id);
//task blogs
$taskBlog = Blog::get_personal_task_list();

if (isset($flag) && $flag == '1') {
    $action = "manage_tasks";
    Blog::display_assign_task_form($blog_id);
}

$user_task = false;

$course_id = api_get_course_int_id();

if (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) {
    $task_id = (int) $_GET['task_id'];
} else {
    $task_id = 0;
    $tbl_blogs_tasks_rel_user = Database:: get_course_table(TABLE_BLOGS_TASKS_REL_USER);

    $sql = "SELECT COUNT(*) as number
			FROM ".$tbl_blogs_tasks_rel_user."
			WHERE
			    c_id = $course_id AND
				blog_id = ".$blog_id." AND
				user_id = ".api_get_user_id()." AND
				task_id = ".$task_id;

    $result = Database::query($sql);
    $row = Database::fetch_array($result);

    if ($row['number'] == 1) {
        $user_task = true;
    }
}

$tpl = new Template($nameTools);
$tpl->setHelp('Blogs');
$tpl->assign('title', $titleBlog);
$tpl->assign('description', $descriptionBlog);
$tpl->assign('id_blog', $idBlog);
$tpl->assign('calendar', $calendarBlog);
$tpl->assign('search', $searchBlog);
$tpl->assign('task', $taskBlog);
$blogLayout = null;

switch ($action) {
    case 'new_post':
        if (api_is_allowed('BLOG_'.$blog_id, 'article_add', $user_task ? $task_id : 0)) {
            // we show the form if
            // 1. no post data
            // 2. there is post data and the required field is empty
            if (!$_POST OR (!empty($_POST) AND empty($_POST['title']))) {
                // if there is post data there is certainly an error in the form
                if ($_POST) {
                    Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'));
                }
                $formAdd = Blog::display_form_new_post($blog_id);
            } else {
                if (isset($_GET['filter']) && !empty($_GET['filter'])) {
                    Blog::display_day_results($blog_id, Database::escape_string($_GET['filter']));
                } else {
                    Blog::display_blog_posts($blog_id);
                }
            }
        } else {
            api_not_allowed();
        }
        $tpl->assign('content', $formAdd);
        $blogLayout = $tpl->get_template('blog/layout.tpl');
        break;
    case 'view_post' :
        $postArticle = Blog::display_post($blog_id, intval($_GET['post_id']));
        $tpl->assign('post', $postArticle);
        $blogLayout = $tpl->get_template('blog/post.tpl');
        break;
    case 'edit_post' :
        $task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;
        if (api_is_allowed('BLOG_'.$blog_id, 'article_edit', $task_id)) {
            // we show the form if
            // 1. no post data
            // 2. there is post data and the required field is empty
            if (!$_POST OR (!empty($_POST) AND empty($_POST['post_title']))) {
                // if there is post data there is certainly an error in the form
                $formEdit = Blog::display_form_edit_post($blog_id, intval($_GET['post_id']));
                $tpl->assign('content', $formEdit);
                $blogLayout = $tpl->get_template('blog/layout.tpl');
                
                if ($_POST) {
                    $post = Blog::display_post($blog_id, intval($_GET['post_id']));
                    $tpl->assign('post', $post);
                    $blogLayout = $tpl->get_template('blog/post.tpl');
                }
            }
        } else {
            api_not_allowed();
        }
        
        break;
    case 'manage_members' :
        $manage = null;
        if (api_is_allowed('BLOG_'.$blog_id, 'member_management')) {
            $manage .= Blog::display_form_user_subscribe($blog_id);
            $manage .= Blog::display_form_user_unsubscribe($blog_id);
        } else {
            api_not_allowed();
        }
        $tpl->assign('content', $manage);
        $blogLayout = $tpl->get_template('blog/layout.tpl');
        break;
    case 'manage_rights' :
        $manage = Blog::display_form_user_rights($blog_id);
        $tpl->assign('content', $manage);
        $blogLayout = $tpl->get_template('blog/layout.tpl');
        break;
    case 'manage_tasks' :
        if (api_is_allowed('BLOG_'.$blog_id, 'task_management')) {
            $task = null;
            if (isset($_GET['do']) && $_GET['do'] == 'add') {
                $task .= Blog::display_new_task_form($blog_id);
            }
            if (isset($_GET['do']) && $_GET['do'] == 'assign') {
                $task .= Blog::display_assign_task_form($blog_id);
            }
            if (isset($_GET['do']) && $_GET['do'] == 'edit') {
                $task .= Blog::display_edit_task_form(
                    $blog_id,
                    intval($_GET['task_id'])
                );
            }
            if (isset($_GET['do']) && $_GET['do'] == 'edit_assignment') {
                $taks .= Blog:: display_edit_assigned_task_form(
                    $blog_id,
                    intval($_GET['task_id']),
                    intval($_GET['user_id'])
                );
            }
            $task .= Blog::display_task_list($blog_id);

            $task .= Blog::display_assigned_task_list($blog_id);


            $tpl->assign('content', $task);
            $blogLayout = $tpl->get_template('blog/layout.tpl');
        } else {
            api_not_allowed();
        }

        break;
    case 'execute_task' :
        if (isset ($_GET['post_id'])) {
            $post = Blog::display_post($blog_id, intval($_GET['post_id']));
            $tpl->assign('post', $post);
            $blogLayout = $tpl->get_template('blog/post.tpl');
        } else {
            $taskPost = Blog::display_select_task_post($blog_id, intval($_GET['task_id']));

            $tpl->assign('content', $taskPost);

            $blogLayout = $tpl->get_template('blog/layout.tpl');
        }
        break;
    case 'view_search_result' :
        $listArticles = Blog:: display_search_results($blog_id, Database::escape_string($_GET['q']));
        $titleSearch = get_lang('SearchResults');
        $tpl->assign('search', $titleSearch);
        $tpl->assign('articles', $listArticles);
        $blogLayout = $tpl->get_template('blog/blog.tpl');
        break;
    case '':
    default:
        if (isset ($_GET['filter']) && !empty ($_GET['filter'])) {
            $listArticles = Blog::display_day_results($blog_id, Database::escape_string($_GET['filter']));
            $dateSearch = api_format_date($_GET['filter'], DATE_FORMAT_LONG);
            $titleSearch = get_lang('PostsOf').' '.$dateSearch;
            $tpl->assign('search', $titleSearch);
            $tpl->assign('articles', $listArticles);
            $blogLayout = $tpl->get_template('blog/blog.tpl');
        } else {
            $listArticles = Blog::display_blog_posts($blog_id);
            $tpl->assign('articles', $listArticles);
            $blogLayout = $tpl->get_template('blog/blog.tpl');
        }
        break;
}

$content = Display::return_introduction_section(TOOL_BLOGS);
$content .= $tpl->fetch($blogLayout);
$tpl->assign('actions', implode(PHP_EOL, $actionsLeft));
$tpl->assign('content', $content);
$tpl->display_one_col_template();
