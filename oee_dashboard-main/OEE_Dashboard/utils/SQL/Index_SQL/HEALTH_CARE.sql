-- Script สร้าง Index เพื่อเพิ่มประสิทธิภาพให้ sp_GetMissingParameters
-- รันเพียงครั้งเดียวเท่านั้น

-- 1. สร้าง Index บนตาราง IOT_TOOLBOX_PARTS เพื่อช่วยให้การค้นหา DISTINCT (line, model, part_no) เร็วขึ้น
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Parts_HealthCheck' AND object_id = OBJECT_ID('dbo.IOT_TOOLBOX_PARTS'))
BEGIN
    PRINT 'Creating index on IOT_TOOLBOX_PARTS... This may take a few minutes on a large table.';
    CREATE NONCLUSTERED INDEX IX_Parts_HealthCheck
    ON dbo.IOT_TOOLBOX_PARTS (line, model, part_no);
    PRINT 'Index on IOT_TOOLBOX_PARTS created successfully.';
END
ELSE
BEGIN
    PRINT 'Index IX_Parts_HealthCheck on IOT_TOOLBOX_PARTS already exists.';
END
GO

-- 2. สร้าง Index บนตาราง IOT_TOOLBOX_PARAMETER เพื่อช่วยให้การค้นหา (NOT EXISTS) เร็วขึ้น
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Parameter_HealthCheck' AND object_id = OBJECT_ID('dbo.IOT_TOOLBOX_PARAMETER'))
BEGIN
    PRINT 'Creating index on IOT_TOOLBOX_PARAMETER...';
    CREATE NONCLUSTERED INDEX IX_Parameter_HealthCheck
    ON dbo.IOT_TOOLBOX_PARAMETER (line, model, part_no);
    PRINT 'Index on IOT_TOOLBOX_PARAMETER created successfully.';
END
ELSE
BEGIN
    PRINT 'Index IX_Parameter_HealthCheck on IOT_TOOLBOX_PARAMETER already exists.';
END
GO