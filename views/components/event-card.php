<div class="col-12 col-md-6 col-lg-4">
    <div class="card event-card shadow">
        <img src="<?= asset($event['banner']) ?>" class="card-img-top" alt="<?= strlen($event['title']) ?>">
        <div class="card-body">
            <h5 class="card-title"><?= strlen($event['title']) > 30 ? substr($event['title'], 0, 30) . " ..." : $event['title'] ?></h5>
            <p class="card-text"><?= strlen($event['description']) > 45 ?  substr($event['description'], 0, 45) . " ..." : $event['description'] ?></p>
            <p><strong>Date:</strong> <?= $event['date'] ?></p>
            <div class="d-flex justify-content-end align-items-center">
                <!-- <a href="#" class="btn btn-primary">Register Now</a> -->
                <a href="<?= route('event.show', ['slug' => $event['slug']]) ?>" class="btn btn-primary">Details</a>
            </div>
        </div>
    </div>
</div>