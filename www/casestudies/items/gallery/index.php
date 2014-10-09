<?php

include_once '../../../config.php';
include_once 'includes/header.php';

// Which activity do you want to load?
$activityRef = 'gallery_1';

if (isset($_GET['activity_reference'])) {
    $activityRef = $_GET['activity_reference'];
}

$studentid = isset($_GET['user']) ? $_GET['user'] : $studentid;

include './itemsRequest.php';

?>

<div class="jumbotron section">
    <div class="overview">
        <h1>Items API – Inline Gallery Style</h1>
        <p>Demonstrates how simply you can style each <em>item</em> in an activity.</p>
    </div>
</div>

<div class="gallery-section section">
    <section class="gallery">
        <div class="row">
            <?php foreach ($items as $reference) { ?>
            <div class="col-md-4 pod">
                <div class="pod-inner">
                    <div class="card">
                        <span class="learnosity-item" data-reference="<?php echo $reference; ?>"></span>
                        <button type="button" class="btn btn-primary save">Save</button>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </section>
</div>

<!-- Container for the items api to load into -->
<script src="//items.learnosity.com/"></script>
<script src="//events.staging.learnosity.com"></script>
<script>
    var initOptions = <?php echo $itemsRequest; ?>,
        eventOptions = {
            readyListener: function () {
                init();
            }
        },
        itemsApp = LearnosityItems.init(initOptions, eventOptions);

    initOptions.config = {
        eventbus: true
    };
    var eventsApp = LearnosityEvents.init(initOptions);

    function init () {
        $('.card').on('click', function (el) {
            if (!$(this).hasClass('active')) {
                var $item = $(this).find('div.learnosity-item');
                toggleItem($item, $(this));
            }
        });

        $('.card .save').on('click', function (el) {
            var $card = $(this).closest('.card');
            var $item = $card.find('div.learnosity-item');
            toggleItem($item, $card);
            saveItem($item.data('reference'));
            return false;
        });
    }

    function toggleItem($item, $card) {
        $('.pod').toggle();
        $item.closest('.pod').toggleClass('col-md-4').animate({
            width: "toggle",
            height: "toggle",
            opacity: "toggle"
        });
        $card.toggleClass('active');
    }

    function saveItem(reference) {
        itemsApp.save();

        var attempted = false;
        itemsApp.attemptedItems(function (items) {
            attempted = $.inArray(reference, items) !== -1;
        });

        if (!attempted) {
            return;
        }

        var responseIds = [];
        itemsApp.getItems(
            function (items) {
                responseIds = items[reference].response_ids;
            }
        );

        var itemScore = {
            score: 0,
            max_score: 0
        };
        itemsApp.getScores(
            function (responses) {
                for (var i=0; i < responseIds.length; i++) {
                    var questionScore = responses[responseIds[i]];
                    if (questionScore && questionScore.max_score) {
                        itemScore.score += questionScore.score;
                        itemScore.max_score += questionScore.max_score;
                    }
                }
            }
        );

        sendEvent(reference, itemScore);
    }

    function sendEvent(reference, score) {
        eventsApp.publish({
            events: [{
                kind: 'assess_logging',
                actor: {
                    account: {
                        homePage: '<?php echo $consumer_key; ?>',
                        name: '<?php echo $studentid; ?>'
                    },
                    objectType: 'Agent'
                },
                verb: {
                    id: 'http://adlnet.gov/expapi/verbs/scored',
                    display: {
                        'en-US': 'scored'
                    }
                },
                object: {
                    id: 'https://xapi.learnosity.com/activities/org/1/pool/null/activity/' +
                        initOptions.request.activity_id + '/item/' + reference,
                    objectType: 'Activity',
                    definition: {
                        extensions: {
                            data: {
                                itemReference: reference,
                                score: score.score,
                                maxScore: score.max_score
                            }
                        }
                    }
                }
            }]
        });
    }
</script>

<?php
    include_once 'includes/footer.php';