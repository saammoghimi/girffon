// =========================
// سیستم Note (یادداشت) - نسخه دیباگ
// =========================

console.log("🔍 شروع بارگذاری note.js");

document.addEventListener('DOMContentLoaded', function() {
    console.log("📝 DOMContentLoaded اجرا شد");

    // پیدا کردن عناصر
    const noteAddBtn = document.getElementById("cdpNoteAddBtn");
    const notePanel = document.getElementById("cdpNotePanel");
    const noteTextarea = document.getElementById("cdpNoteText");
    const noteCloseBtn = document.querySelector(".cdp-note-close");
    const noteCloseButtons = document.querySelectorAll("[data-note-close]");
    const noteSaveBtn = document.querySelector("[data-note-save]");

    // چک کردن وجود عناصر
    console.log("🔍 بررسی عناصر:", {
        noteAddBtn: noteAddBtn ? "✅ پیدا شد" : "❌ پیدا نشد",
        notePanel: notePanel ? "✅ پیدا شد" : "❌ پیدا نشد",
        noteTextarea: noteTextarea ? "✅ پیدا شد" : "❌ پیدا نشد",
        noteCloseBtn: noteCloseBtn ? "✅ پیدا شد" : "❌ پیدا نشد",
        noteSaveBtn: noteSaveBtn ? "✅ پیدا شد" : "❌ پیدا نشد"
    });

    if (!noteAddBtn) {
        console.error("❌ دکمه cdpNoteAddBtn پیدا نشد!");
        return;
    }

    if (!notePanel) {
        console.error("❌ پنل cdpNotePanel پیدا نشد!");
        return;
    }

    console.log("✅ همه عناصر پیدا شدند");

    // تابع باز کردن پنل
    function openNotePanel() {
        console.log("🚀 تابع openNotePanel اجرا شد");
        notePanel.setAttribute("data-visible", "true");
        console.log("📊 وضعیت پنل:", {
            visible: notePanel.getAttribute("data-visible")
        });
    }

    // تابع بستن پنل
    function closeNotePanel() {
        console.log("🚪 تابع closeNotePanel اجرا شد");
        notePanel.setAttribute("data-visible", "false");
    }

    // کلیک روی دکمه +
    console.log("🔗 اضافه کردن event listener به دکمه");
    noteAddBtn.addEventListener("click", function(e) {
        console.log("🖱️ دکمه + کلیک شد!");
        e.stopPropagation();
        openNotePanel();
    });

    // بستن با دکمه X
    if (noteCloseBtn) {
        noteCloseBtn.addEventListener("click", function() {
            console.log("🖱️ دکمه بستن X کلیک شد");
            closeNotePanel();
        });
    }

    // بستن با دکمه‌های data-note-close
    noteCloseButtons.forEach((btn, index) => {
        btn.addEventListener("click", function() {
            console.log(`🖱️ دکمه بستن ${index + 1} کلیک شد`);
            closeNotePanel();
        });
    });

    // ذخیره یادداشت
    if (noteSaveBtn && noteTextarea) {
        noteSaveBtn.addEventListener("click", function() {
            const text = noteTextarea.value.trim();
            localStorage.setItem("cdpNote", text);
            console.log("💾 یادداشت ذخیره شد:", text);
            
            noteSaveBtn.textContent = "Saved ✓";
            setTimeout(() => {
                noteSaveBtn.textContent = "Save";
                closeNotePanel();
            }, 1000);
        });

        // بارگذاری یادداشت قبلی
        const savedNote = localStorage.getItem("cdpNote") || "";
        if (savedNote) {
            noteTextarea.value = savedNote;
            console.log("📖 یادداشت قبلی بارگذاری شد");
        }
    }

    // بستن با Escape
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape" && notePanel.getAttribute("data-visible") === "true") {
            console.log("⌨️ کلید Escape فشرده شد");
            closeNotePanel();
        }
    });

    console.log("✅ سیستم Note آماده است!");
});

console.log("🏁 پایان فایل note.js");