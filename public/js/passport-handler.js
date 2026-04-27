// passport-handler.js
window.PassportScanner = (function() {
    let MyScan = null;
    let isReconnecting = false;

    // --- เพิ่มฟังก์ชัน Helper สำหรับจัดการ Format ข้อมูล ---
    function formatPassportDate(dateStr) {
        if (!dateStr || dateStr.length !== 6) return "-";
        const year = parseInt(dateStr.substring(0, 2));
        const month = dateStr.substring(2, 4);
        const day = dateStr.substring(4, 6);
        // แปลงปี 2 หลัก เป็น 4 หลัก (ตัดรอบที่ปี 50)
        const fullYear = year < 50 ? "20" + year : "19" + year;
        return `${day}/${month}/${fullYear}`;
    }

    // Try to use portrait/headshot image first; fallback to document image.
    function resolvePassportOwnerPhoto(file, info) {
        const candidateKeys = [
            "FaceImage", "faceImage", "Portrait", "portrait", "Headshot", "headshot",
            "OwnerPhoto", "ownerPhoto", "PhotoFace", "photoFace", "photo", "Photo"
        ];

        const sources = [info, file];
        for (const src of sources) {
            if (!src || typeof src !== "object") continue;
            for (const key of candidateKeys) {
                const value = src[key];
                if (typeof value === "string" && value.trim().length > 40) {
                    return value.trim();
                }
            }
        }

        // Final fallback: keep existing behavior.
        return (file && (file.base64 || file.Photo)) || "";
    }

    const view = {
        addLogEntry: function(log, msgType) {
            console.log(`[PassportScan][${msgType}]`, log);
        },
        displayLoadingMask: function(isVisible, options) {
            if (options.onLoading) options.onLoading(isVisible);
        },
        updateStatus: function(isOnline, options) {
            if (options.onStatusChange) options.onStatusChange(isOnline);
        }
    };

    const imageData = { newImage: null };
    function createProxyHandler(options) {
        return {
            set: function(obj, prop, value) {
                if (prop === "newImage" && value) {
                    if (options.onDataReceived) {
                        const info = value.ocrText || value; // รองรับทั้งโครงสร้าง direct และ nested

                        console.log(info)
                        // --- แก้ไขการ Map ข้อมูลตรงนี้ให้เข้ากับ putPassportToScreen ---
                        const formattedData = {
                            ID_Number: info.DocumentNo || info.PassportNo || "-",
                            ENName: (info.Givenname + " " + info.Familyname).trim() || info.Name || "-",
                            ThaiName: "",
                            DOB: formatPassportDate(info.Birthday),
                            ExpireDate: formatPassportDate(info.Dateofexpiry),
                            Address: info.Nationality || "-", // ใช้ Nationality แทนที่อยู่
                            Photo: resolvePassportOwnerPhoto(value, info),
                            Raw: info
                        };

                        options.onDataReceived(formattedData);
                    }
                    return true;
                }
                return Reflect.set(...arguments);
            }
        };
    }

    // ฟังก์ชันสำหรับ Wrap Library (คงเดิม)
    function wrapLib(instance) {
        Object.getOwnPropertyNames(Object.getPrototypeOf(instance))
            .filter(prop => typeof instance[prop] === "function" && prop !== "constructor")
            .forEach(methodName => {
                const originalMethod = instance[methodName];
                instance[methodName] = async function(...args) {
                    try {
                        let result = await originalMethod.apply(this, args);
                        return result;
                    } catch (e) {
                        view.addLogEntry(`Error in ${methodName}: ${e.message}`, "error");
                        throw e;
                    }
                };
            });
    }

    return {
        init: async function(options) {
            if (isReconnecting) return;
            try {
                MyScan = new WebFxScan();
                wrapLib(MyScan);
                let proxyImageData = new Proxy(imageData, createProxyHandler(options));

                await MyScan.connect({ ip: "127.0.0.1", port: "17778" });
                // Show ready earlier at step1 once scanner service is connected.
                view.updateStatus(true, options);

                await MyScan.setAutoScanCallback({
                    callback: (file, errCode) => {
                        if (options.onLoading) options.onLoading(false);
                        if (errCode === 0) {
                            proxyImageData.newImage = file;
                        } else if (errCode < 0) {
                            view.updateStatus(false, options);
                        }
                    },
                });

                await MyScan.setBeforeAutoScanCallback({
                    callback: () => { if (options.onLoading) options.onLoading(true); }
                });

                await MyScan.init();

                const { data: deviceListData } = await MyScan.getDeviceList();
                let targetDevice = "A62";
                if (deviceListData && deviceListData.options && deviceListData.options.length > 0) {
                    const found = deviceListData.options.find(d => d.deviceName.includes("A62"));
                    targetDevice = found ? found.deviceName : deviceListData.options[0].deviceName;
                }

                await MyScan.setScanner({
                    deviceName: targetDevice,
                    source: "Camera",
                    recognizeType: "passport",
                    resolution: 300,
                    autoScan: true,
                });

                await MyScan.scan();
                isReconnecting = false;

            } catch (e) {
                view.updateStatus(false, options);
                isReconnecting = true;
                setTimeout(() => {
                    isReconnecting = false;
                    this.init(options);
                }, 5000);
            }
        }
    };
})();
