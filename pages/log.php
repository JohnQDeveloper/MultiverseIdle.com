<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1>Game Log</h1>

        <h3>Wire Transfers</h3>
        <?php if (empty($wireLog)): ?>
        <p>No wire transfers yet.</p>
        <?php else: ?>
        <table class="log-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Amount</th>
                    <th>Commodity</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($wireLog as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($entry['sender_username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($entry['recipient_username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format((int)$entry['amount']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($entry['commodity']), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
