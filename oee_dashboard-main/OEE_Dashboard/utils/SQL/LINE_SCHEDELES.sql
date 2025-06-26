-- ตรวจสอบว่ามีตารางนี้อยู่แล้วหรือไม่ ถ้ามีให้ลบออกก่อน (เพื่อให้รันซ้ำได้)
IF OBJECT_ID('dbo.LINE_SCHEDULES', 'U') IS NOT NULL
    DROP TABLE dbo.LINE_SCHEDULES;
GO

-- สร้างตารางสำหรับเก็บตารางเวลาทำงานของแต่ละไลน์
CREATE TABLE dbo.LINE_SCHEDULES (
    id INT IDENTITY(1,1) PRIMARY KEY,
    line VARCHAR(50) NOT NULL,
    shift_name VARCHAR(50) NOT NULL, -- เช่น 'Day', 'Night'
    start_time TIME NOT NULL,         -- เวลาเริ่มกะ
    end_time TIME NOT NULL,           -- เวลาจบกะ
    planned_break_minutes INT NOT NULL DEFAULT 0, -- เวลารวมพักที่วางแผนไว้ (นาที)
    is_active BIT NOT NULL DEFAULT 1 -- สถานะการใช้งาน (1 = ใช้งาน, 0 = ไม่ใช้งาน)
);
GO

-- สร้าง UNIQUE INDEX เพื่อป้องกันการสร้างกะซ้ำซ้อนในไลน์เดียวกัน
CREATE UNIQUE INDEX UQ_line_shift ON dbo.LINE_SCHEDULES (line, shift_name);
GO

-- === ใส่ข้อมูลตัวอย่าง ===
PRINT 'Inserting sample data into LINE_SCHEDULES...';
INSERT INTO dbo.LINE_SCHEDULES (line, shift_name, start_time, end_time, planned_break_minutes)
VALUES
('Assembly', 'Day', '08:00:00', '17:00:00', 75),   -- กะวัน Assembly ทำงาน 8 โมงเช้า ถึง 5 โมงเย็น พัก 75 นาที
('Assembly', 'Night', '20:00:00', '05:00:00', 75), -- กะดึก Assembly ทำงาน 2 ทุ่ม ถึง ตี 5 (ข้ามวัน)
('Paint', 'Day', '07:00:00', '16:00:00', 60),      -- กะวัน Paint
('Spot', 'Day', '08:00:00', '17:00:00', 60),
('Bend', 'Day', '08:00:00', '17:00:00', 60),
('Press', 'Day', '08:00:00', '17:00:00', 60);
GO

PRINT 'Table LINE_SCHEDULES created and populated successfully.';