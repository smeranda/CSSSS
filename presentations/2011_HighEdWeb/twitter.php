<?php

include_once('config.php');
// Plug in the twitter search term here
$search_term = '#soc11';
$highlight_term = '#soc11q';

// For use with a database

// Format Twitter's date for insert into MySQL
function unixToMySQL($timestamp) {
    return date('Y-m-d H:i:s', $timestamp);
}

// Setup connection to the Database
mysql_connect('localhost', $username, $password) or die('Could not connect: ' . mysql_error());
mysql_select_db($db) or die();

if ($_GET['presentation'] != 'summary'){
    // Get the most recent tweet, so we can start with it.
    // Twitter uses the string value (which is different than the INT)
    $latest_tweet = mysql_query('SELECT `id_str` from `tweets` ORDER BY `id_str` DESC LIMIT 1') or die();
    
    // Store a blank value in case the DB returns no results
    $since_id = '';
    
    // Save the returned value
    while ($row = mysql_fetch_assoc($latest_tweet)) {
        $since_id = '&since_id='.$row['id_str'];
    }
    
    // Build the JSON URL
    $twitter_json = sprintf('http://search.twitter.com/search.json?q=%s&rpp=50&result_type=recent', urlencode($search_term.' OR '.$highlight_term)).$since_id;
    
    $tweets = json_decode(file_get_contents($twitter_json), true);
    
    $total_tweets = count($tweets['results']);
    $count = 0;
    foreach ($tweets['results'] as $tweet):
        if (strpos($tweet['text'], $highlight_term) === false){
            $highlight = 0;
        } else {
            $highlight = 1;
        }
        $query = "INSERT INTO `tweets` (`created_at`, `from_user`, `from_user_id`, `from_user_id_str`, `geo`, `id`, `id_str`, `profile_image_url`, `source`, `text`, `to_user_id`, `to_user_id_str`, `highlight`) VALUES ('".unixToMySQL(strtotime($tweet['created_at']))."', '".mysql_real_escape_string($tweet['from_user'])."', '".$tweet['from_user_id']."', '".mysql_real_escape_string($tweet['from_user_id_str'])."', '".$tweet['geo']['coordinates']."', '".$tweet['id']."', '".mysql_real_escape_string($tweet['id_str'])."', '".mysql_real_escape_string($tweet['profile_image_url'])."', '".mysql_real_escape_string($tweet['source'])."', '".mysql_real_escape_string($tweet['text'])."', '".$tweet['to_user_id']."', '".mysql_real_escape_string($tweet['to_user_id_str'])."', '".$highlight."')";
        mysql_query($query) or die();
        
        if ($count < 1): //we only have room to display one at a time, but we'll keep them all
    ?>
        <li>
            <?php if (strpos($tweet['text'], $highlight_term) === false) {
                echo '<blockquote>';
            } else {
                echo '<blockquote class="highlight">';
            } 
            ?>
                <img src="<?php echo $tweet['profile_image_url'];?>" alt="<?php echo $tweet['from_user']; ?>'s Profile Pic" /> 
                <cite>
                    <a href="http://twitter.com/<?php echo $tweet['from_user']; ?>"><?php echo $tweet['from_user']; ?></a>:
                </cite>
                <q>
                    <?php echo $tweet['text']; ?>
                </q>
            </blockquote>
        </li>
    <?php 
        endif;
    $count = $count+1;
    endforeach;
}  else { // end presentation for inidvidual tweets

    $q_query = 'SELECT * from `tweets` WHERE `highlight` = 1 ORDER BY `id` DESC';
    $tweets = mysql_query($q_query);
    
    if(!$tweets) {
        die('Rats, nothing here');
    }
?>
    <h2>Today's Backchannel Summary</h2>
    <section class="col highlight">
        <h3>Questions <span><?php echo(mysql_num_rows($tweets)); ?></span></h3>
        <ul>
        <?php
            $q_count = 0;
            while ($tweet = mysql_fetch_assoc($tweets)) :
                if ($q_count < 100) :
        ?>
            <li>
                <blockquote>
                    <img src="<?php echo $tweet['profile_image_url'];?>" alt="<?php echo $tweet['from_user']; ?>'s Profile Pic" /> 
                    <cite>
                        <a href="http://twitter.com/<?php echo $tweet['from_user']; ?>"><?php echo $tweet['from_user']; ?></a>:
                    </cite>
                    <q>
                        <?php echo $tweet['text']; ?>
                    </q>
                </blockquote>
            </li>
        <?php 
                endif;
            $q_count = $q_count + 1; 
            endwhile; ?>
        </ul>
    </section>
    <?php 
        mysql_free_result($tweets);
        
        $c_query = 'SELECT * from `tweets` WHERE `highlight` = 0 ORDER BY `id` DESC';
        $tweets = mysql_query($c_query);
        
        if(!$tweets) {
            die('Rats, nothing here');
        }
    ?>
    <section class="col">
        <h3>Chatter <span><?php echo(mysql_num_rows($tweets)); ?></span></h3>
        <ul>
        <?php
            $c_count = 0;
            while ($tweet = mysql_fetch_assoc($tweets)) :
                if ($c_count < 100) :
        ?>
            <li>
                <blockquote>
                    <img src="<?php echo $tweet['profile_image_url'];?>" alt="<?php echo $tweet['from_user']; ?>'s Profile Pic" /> 
                    <cite>
                        <a href="http://twitter.com/<?php echo $tweet['from_user']; ?>"><?php echo $tweet['from_user']; ?></a>:
                    </cite>
                    <q>
                        <?php echo $tweet['text']; ?>
                    </q>
                </blockquote>
            </li>
        <?php 
                endif;
            $c_count = $c_count + 1; 
            endwhile; ?>
        </ul>
    </section>
<?php
}
?>