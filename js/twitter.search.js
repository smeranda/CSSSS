/*
*
*    This grabs the HTML output of the twitter.php script, which has the search terms hardcoded.
*
*/


(function() {
    
    var bc = window.Backchannel = function() {
        
    }
    
    // Assign values
    bc.path = 'twitter.php';
    bc.interval = 30000;
    bc.running = false;
    
    start = function() {
        $('#backchannel').addClass('display');
        
        if(bc.running){ //check to make sure we haven't already started the backchannel
            return;
        } else {
            bc.running = true;
        }
        bc_ = setInterval(
            "get()", 
            bc.interval
        )
    };
    
    end = function() {
        console.log('stopping twitter search');
        clearInterval(bc_);
        $('#tweets').remove();
    }
    
    get = function() {
        tweets = $('#tweets');
        $.get(bc.path, function(data){
            $(tweets).append(data);
            setTimeout( // we need to delay the placing of the displayed class
                function(){
                    $(tweets).children('li').addClass('displayed')
                }, 100
            );
        });
    };
    
    summarize = function(summary_slide){
        console.log('building summary');
        $.get(bc.path+"?presentation=summary", function(data){
            $(summary_slide).html(data);
        });
    }
    
    bc.notify = function(slide){
        // Check if we're on the slide to introduce the backchannel
        if ($(slide).attr('id') == 'bc_description') {
            start();
        }
        // Check if the next slide is the summary, if so prepare the summary
        if ($(slide).next().attr('id') == 'bc_summary') {
            summarize($(slide).next());
        }        
        // Check if we're on the slide to summarize backchannel
        if ($(slide).attr('id') == 'bc_summary') {
            end();
        }
    };
})();