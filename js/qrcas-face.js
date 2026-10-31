/**
 * Nhận diện khuôn mặt (face-api.js: TinyFaceDetector + landmarks 68 điểm + ResNet-34 128-D) và
 * thử thách "người thật" (chớp mắt / quay đầu do SERVER chọn ngẫu nhiên mỗi lần) chạy hoàn toàn trên trình duyệt.
 * Vector đặc trưng 128 chiều được gửi lên server để so khớp — ảnh gốc không được lưu lâu dài.
 *
 * Cần nạp trước: lib/face-api/face-api.js  (biến toàn cục `faceapi`)
 *
 * GIỚI HẠN CẦN NÊU TRONG BÁO CÁO: xử lý ở phía client nên người có kỹ năng can thiệp JavaScript
 * vẫn có thể gửi dữ liệu giả. Vì vậy hệ thống KHÔNG dựa vào một lớp duy nhất: passkey sinh trắc học, QR động,
 * phát hiện trùng khuôn mặt giữa các tài khoản ở phía server và chấm điểm rủi ro cùng phối hợp.
 * Khi cần mức bảo đảm cao hơn, thay bước này bằng dịch vụ liveness phía server
 * (AWS Rekognition Face Liveness, Azure Face Liveness...).
 */
(function(global) {
    'use strict';

    // Có thể tự host các weight tại lib/face-api/models bằng cách gán biến
    // này trước khi nạp file. Mặc định dùng bản cố định trên CDN để tính năng
    // hoạt động ngay cả khi thư mục weight chưa được cài vào dự án.
    var MODEL_URL = global.QRCAS_FACE_MODEL_URL || 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
    var DET_OPTS = null;
    var loaded = false;
    var stream = null;

    function sleep(ms) { return new Promise(function(r) { setTimeout(r, ms); }); }

    function dist2(a, b) { var dx = a.x - b.x, dy = a.y - b.y; return Math.sqrt(dx * dx + dy * dy); }

    function mean(arr) { return arr.reduce(function(s, v) { return s + v; }, 0) / (arr.length || 1); }

    function ear(pts, i) {
        // pts: mảng 68 điểm; i: chỉ số điểm đầu của mắt (36 hoặc 42)
        var p = function(k) { return pts[i + k]; };
        var v = dist2(p(1), p(5)) + dist2(p(2), p(4));
        var h = 2 * dist2(p(0), p(3));
        return h ? v / h : 0;
    }

    function yawRatio(pts) {
        // Vị trí mũi giữa hai đầu hàm (raw video, chưa lật): ~0.5 khi nhìn thẳng
        var left = pts[0].x, right = pts[16].x, nose = pts[30].x;
        var w = right - left;
        return w ? (nose - left) / w : 0.5;
    }

    async function loadModels() {
        if (loaded) return;
        if (!global.faceapi) throw new Error('Chưa nạp được thư viện nhận diện khuôn mặt.');
        await faceapi.tf.ready();
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        DET_OPTS = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
        loaded = true;
    }

    async function startCamera(video, facingMode) {
        stopCamera();
        var mode = facingMode === 'environment' ? 'environment' : 'user';
        var constraints = {
            video: { facingMode: { ideal: mode }, width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false
        };
        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (error) {
            if (mode !== 'environment') throw error;
            // Nếu máy không có camera sau thì dùng camera còn lại thay vì chặn điểm danh.
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'user' }, width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false
            });
        }
        video.srcObject = stream;
        video.setAttribute('playsinline', 'true');
        video.muted = true;
        await video.play();
        // Chờ khung hình đầu tiên
        for (var i = 0; i < 50 && !video.videoWidth; i++) { await sleep(50); }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function(t) { t.stop(); });
            stream = null;
        }
    }

    /** Phát hiện đúng 1 khuôn mặt. Trả về null nếu không có. Có >1 mặt => ném lỗi. */
    async function detectOne(video) {
        var all = await faceapi.detectAllFaces(video, DET_OPTS).withFaceLandmarks().withFaceDescriptors();
        if (!all || all.length === 0) return null;
        if (all.length > 1) { var e = new Error('multi_face'); e.code = 'multi_face'; throw e; }
        var d = all[0];
        return {
            descriptor: Array.prototype.slice.call(d.descriptor),
            points: d.landmarks.positions,
            score: d.detection.score,
            box: d.detection.box
        };
    }

    /** Ảnh JPEG nhỏ (data URL) từ video — dùng làm bằng chứng cho giảng viên/quản trị viên duyệt. */
    function thumb(video, maxW) {
        maxW = maxW || 240;
        var w = video.videoWidth, h = video.videoHeight;
        if (!w || !h) return null;
        var s = Math.min(1, maxW / w);
        var c = document.createElement('canvas');
        c.width = Math.round(w * s);
        c.height = Math.round(h * s);
        c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);
        return c.toDataURL('image/jpeg', 0.6);
    }

    function averageDescriptors(list) {
        var out = new Array(128).fill(0);
        list.forEach(function(d) { for (var i = 0; i < 128; i++) out[i] += d[i]; });
        return out.map(function(v) { return v / list.length; });
    }

    function euclid(a, b) {
        var s = 0;
        for (var i = 0; i < a.length; i++) { var d = a[i] - b[i]; s += d * d; }
        return Math.sqrt(s);
    }

    /**
     * Thu thập N mẫu khuôn mặt nhìn thẳng để đăng ký. Các mẫu phải nhất quán (cùng một người).
     */
    async function enroll(video, onStatus, samples) {
        samples = samples || 5;
        var got = [], photo = null, tries = 0;
        while (got.length < samples && tries < 80) {
            tries++;
            var r = await detectOne(video);
            if (!r) { onStatus && onStatus('Không thấy khuôn mặt — hãy đưa mặt vào giữa khung hình, đủ sáng.'); await sleep(250); continue; }
            var yaw = yawRatio(r.points);
            if (yaw < 0.4 || yaw > 0.6) { onStatus && onStatus('Hãy nhìn thẳng vào camera.'); await sleep(250); continue; }
            if (r.box.width < video.videoWidth * 0.2) { onStatus && onStatus('Hãy đưa camera lại gần hơn một chút.'); await sleep(250); continue; }
            got.push(r.descriptor);
            if (!photo) photo = thumb(video, 240);
            onStatus && onStatus('Đã lấy mẫu ' + got.length + '/' + samples + '…');
            await sleep(350);
        }
        if (got.length < samples) throw new Error('Không thu đủ mẫu khuôn mặt, vui lòng thử lại nơi đủ sáng.');
        // Các mẫu phải gần nhau, nếu không có thể đang có nhiều người / ảnh chụp bị nhiễu
        for (var i = 1; i < got.length; i++) {
            if (euclid(got[0], got[i]) > 0.5) throw new Error('Các mẫu khuôn mặt không nhất quán, hãy giữ yên đầu và thử lại.');
        }
        return { descriptors: got, photo: photo };
    }

    var CHALLENGE_TEXT = {
        blink: 'Hãy CHỚP MẮT 2 LẦN',
        left: 'Hãy QUAY MẶT SANG TRÁI (theo màn hình) rồi nhìn thẳng lại',
        right: 'Hãy QUAY MẶT SANG PHẢI (theo màn hình) rồi nhìn thẳng lại'
    };

    /**
     * Thử thách người thật do server chọn (blink | left | right), sau đó thu descriptor lúc nhìn thẳng.
     * Trả về { passed, challenge, descriptor, photo }.
     */
    async function livenessAndCapture(video, challenge, onStatus) {
        var deadline = Date.now() + 45000;
        var passed = false;
        var baseline = [];
        var blinkCount = 0, eyesClosed = false;
        var turned = false;
        var sawCenterBefore = false;

        onStatus && onStatus('Nhìn thẳng vào camera…');

        while (Date.now() < deadline && !passed) {
            var r = await detectOne(video);
            if (!r) { await sleep(120); continue; }
            var yaw = yawRatio(r.points);
            var e = (ear(r.points, 36) + ear(r.points, 42)) / 2;

            if (challenge === 'blink') {
                if (baseline.length < 6) { // đo độ mở mắt bình thường
                    if (yaw > 0.38 && yaw < 0.62) baseline.push(e);
                    if (baseline.length === 6) onStatus && onStatus(CHALLENGE_TEXT.blink);
                } else {
                    var base = mean(baseline);
                    if (!eyesClosed && e < base * 0.72) { eyesClosed = true; }
                    else if (eyesClosed && e > base * 0.9) { eyesClosed = false; blinkCount++; }
                    if (blinkCount >= 2) passed = true;
                }
            } else {
                if (!turned) {
                    if (yaw > 0.42 && yaw < 0.58) { sawCenterBefore = true; }
                    if (sawCenterBefore) onStatus && onStatus(CHALLENGE_TEXT[challenge]);
                    // Ảnh video KHÔNG lật: mặt quay sang TRÁI theo màn hình (xem gương) => mũi lệch về x lớn
                    if (sawCenterBefore && ((challenge === 'left' && yaw >= 0.62) || (challenge === 'right' && yaw <= 0.38))) {
                        turned = true;
                        onStatus && onStatus('Tốt! Bây giờ nhìn thẳng vào camera.');
                    }
                } else if (yaw > 0.44 && yaw < 0.56) {
                    passed = true;
                }
            }
            await sleep(90);
        }

        // Thu vector khuôn mặt lúc nhìn thẳng (kể cả khi không vượt thử thách, để server chấm điểm)
        var descs = [], photo = null, tries = 0;
        onStatus && onStatus('Giữ yên, nhìn thẳng…');
        while (descs.length < 3 && tries < 40) {
            tries++;
            var f = await detectOne(video);
            if (f) {
                var y = yawRatio(f.points);
                if (y > 0.4 && y < 0.6) {
                    descs.push(f.descriptor);
                    if (!photo) photo = thumb(video, 240);
                }
            }
            await sleep(150);
        }
        return {
            passed: passed,
            challenge: challenge,
            descriptor: descs.length ? averageDescriptors(descs) : null,
            photo: photo
        };
    }

    global.QRCASFace = {
        loadModels: loadModels,
        startCamera: startCamera,
        stopCamera: stopCamera,
        enroll: enroll,
        livenessAndCapture: livenessAndCapture,
        challengeText: function(c) { return CHALLENGE_TEXT[c] || ''; }
    };
})(window);
