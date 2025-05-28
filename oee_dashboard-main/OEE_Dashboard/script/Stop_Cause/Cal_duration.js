row.classList.add(getSeverityClass(duration, cause));
    
function getSeverityClass(duration, cause) {
    if (["Power Failure", "System Crash"].includes(cause)) return "severe-cause";
    if (duration > 60) return "critical-duration";
    if (duration > 30) return "moderate-duration";
    return "normal-duration";
}
