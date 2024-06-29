<?php
$connection = pg_connect("host=103.122.160.54 dbname=database_single user=logsreader_single password=ix9ijb6w06725sd");
if (!$connection) {
    echo "An error. <br>";
    exit;
}

$results = null;
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $caller = $_GET['caller'] ?? '';
    $receiver = $_GET['receiver'] ?? '';
    $call_direction = $_GET['call_direction'] ?? 'all';
    $ring_time_from = $_GET['ring_time_from'] ?? '';
    $ring_time_to = $_GET['ring_time_to'] ?? '';
    $talk_time_from = $_GET['talk_time_from'] ?? '';
    $talk_time_to = $_GET['talk_time_to'] ?? '';
    $group_name = $_GET['group_name'] ?? '';
    $extension = $_GET['extension'] ?? '';
    $call_id = $_GET['call_id'] ?? '';

    $query = "
        SELECT 
            s.call_id,
            s.src_part_id,
            src_info.display_name AS src_display_name,
            src_info.caller_number AS src_caller_number,
            s.dst_part_id,
            dst_info.display_name AS did_name,
            s.start_time,
            s.end_time,
            c.is_answered,
            c.ringing_dur,
            c.talking_dur,
            max_actions.max_action_id
        FROM 
            cl_segments s
        JOIN 
            cl_calls c ON s.call_id = c.id
        JOIN 
            cl_party_info src_info ON s.src_part_id = src_info.id
        JOIN 
            cl_party_info dst_info ON s.dst_part_id = dst_info.id
        JOIN 
            (SELECT call_id, MAX(action_id) AS max_action_id FROM cl_segments GROUP BY call_id) max_actions ON s.call_id = max_actions.call_id
        WHERE 
            (s.id, s.call_id) IN (
                SELECT MIN(id), call_id 
                FROM cl_segments 
                GROUP BY call_id
            )
    ";

    if (!empty($start_date)) {
        $query .= " AND s.start_time >= '$start_date'";
    }
    if (!empty($end_date)) {
        $query .= " AND s.end_time <= '$end_date'";
    }
    if (!empty($caller)) {
        $query .= " AND src_info.caller_number LIKE '%$caller%'";
    }
    if (!empty($receiver)) {
        $query .= " AND dst_info.display_name LIKE '%$receiver%'";
    }
    if ($status !== 'all') {
        $query .= $status === 'answered' ? " AND c.is_answered = 't'" : " AND c.is_answered = 'f'";
    }
    if (!empty($call_id)) {
        $query .= " AND s.call_id = '$call_id'";
    }

    $query .= " ORDER BY s.call_id";

    $results = pg_query($connection, $query);
    if (!$results) {
        echo "An error occurred.\n";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <div class="h-screen">
        <div class="p-6 bg-zinc-100">
            <div class="bg-white p-4 rounded shadow-md mb-4">
                <form method="get" action="index.php">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-zinc-700">Từ ngày</label>
                            <input type="datetime-local" id="start_date" name="start_date" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-zinc-700">Đến ngày</label>
                            <input type="datetime-local" id="end_date" name="end_date" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-zinc-700">Trạng thái</label>
                            <select id="status" name="status" class="w-full p-2 border rounded">
                                <option value="all">Tất cả</option>
                                <option value="answered">Trả Lời</option>
                                <option value="missed">Không Trả Lời</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-700">Người gọi</label>
                            <input type="text" id="caller" name="caller" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-zinc-700">Người nhận</label>
                            <input type="text" id="receiver" name="receiver" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-zinc-700">Chiều gọi</label>
                            <select id="call_direction" name="call_direction" class="w-full p-2 border rounded">
                                <option value="all">Tất cả</option>
                                <option value="caller">Gọi ra</option>
                                <option value="recipient">Gọi vào</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-700">Thời gian đổ chuông</label>
                            <div class="flex space-x-2">
                                <input type="time" name="ring_time_from" class="w-full p-2 border rounded">
                                <input type="time" name="ring_time_to" class="w-full p-2 border rounded">
                            </div>
                        </div>
                        <div>
                            <label class="block text-zinc-700">Thời gian đàm thoại</label>
                            <div class="flex space-x-2">
                                <input type="time" name="talk_time_from" class="w-full p-2 border rounded">
                                <input type="time" name="talk_time_to" class="w-full p-2 border rounded">
                            </div>
                        </div>
                        <div>
                            <label class="block text-zinc-700">Tên nhóm</label>
                            <select name="group_name" class="w-full p-2 border rounded">
                                <option>Chọn nhóm</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-700">Extension</label>
                            <select name="extension" class="w-full p-2 border rounded">
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-700">Mã cuộc gọi</label>
                            <input type="text" id="call_id" name="call_id" class="w-full p-2 border rounded">
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="reset" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Làm Mới</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Tìm Kiếm</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow-md">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-zinc-200">
                        <th class="p-2 border">#</th>
                        <th class="p-2 border">Mã cuộc gọi</th>
                        <th class="p-2 border">Thời gian</th>
                        <th class="p-2 border">Chiều gọi</th>
                        <th class="p-2 border">Người gọi</th>
                        <th class="p-2 border">Số gọi ra</th>
                        <th class="p-2 border">Người nhận</th>
                        <th class="p-2 border">Trạng thái</th>
                        <th class="p-2 border">Đổ chuông</th>
                        <th class="p-2 border">Đàm thoại</th>
                        <th class="p-2 border">Tổng thời gian</th>
                        <th class="p-2 border">Ngắt máy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results && pg_num_rows($results) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($results)): ?>
                            <?php
                                $starttime = new DateTime($row['start_time']);
                                $starttime = $starttime->format('d-m-Y H:i:s');

                                $timeringing = new DateTime($row['ringing_dur']);
                                $microseconds = $timeringing->format('u');
                                $timeringing = $timeringing->format('H:i:s');

                                $timetalking = new DateTime($row['talking_dur']);
                                $microseconds = $timetalking->format('u');
                                $timetalking = $timetalking->format('H:i:s');

                                $time1 = new DateTime($row['ringing_dur']);
                                $time2 = new DateTime($row['talking_dur']);
                                $totalSeconds = $time1->format('H') * 3600 + $time1->format('i') * 60 + $time1->format('s')
                                    + $time2->format('H') * 3600 + $time2->format('i') * 60 + $time2->format('s');
                                $hours = floor($totalSeconds / 3600);
                                $minutes = floor(($totalSeconds / 60) % 60);
                                $seconds = $totalSeconds % 60;
                                $totaltime = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

                                $status = '';
                                $terminated = '';

                                $number = $row['src_caller_number'];
                                $number = str_replace('Ext.', '', $number);

                                $maxActionId = (int) $row['max_action_id'];

                                if ($maxActionId == 5) {
                                    $terminated = "Terminated by " . $row['src_display_name'];
                                } elseif ($maxActionId == 6) {
                                    $terminated = "Terminated by " . $row['did_name'];
                                } else {
                                    $terminated = "Failed";
                                }

                                if ($row['is_answered'] === 't') {
                                    $status = "Trả Lời";
                                } else {
                                    $status = "Không Trả Lời";
                                    $terminated = "Failed";
                                }
                            ?>
                            <tr>
                                <td class='p-2 border'><input type='checkbox'></td>
                                <td class='p-2 border text-center'><?= $row['call_id'] ?></td>
                                <td class='p-2 border text-center' style='width: 200px;'><?= $starttime ?></td>
                                <td class='p-2 border text-center'>Gọi ra</td>
                                <td class='p-2 border text-center'><?= $row['src_display_name'] ?></td>
                                <td class='p-2 border text-center'><?= $number ?></td>
                                <td class='p-2 border text-center'><?= $row['did_name'] ?></td>
                                <td class='p-2 border text-center' style='width: 150px;'><?= $status ?></td>
                                <td class='p-2 border text-center'><?= $timeringing ?></td>
                                <td class='p-2 border text-center'><?= $timetalking ?></td>
                                <td class='p-2 border text-center'><?= $totaltime ?></td>
                                <td class='p-2 border text-center'><?= $terminated ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="12" class='p-2 border text-center'>No results found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>

