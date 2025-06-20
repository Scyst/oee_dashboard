USE [master]
GO
/****** Object:  Database [oee_db]    Script Date: 21/06/2025 14:29:12 ******/
CREATE DATABASE [oee_db]
 CONTAINMENT = NONE
 ON  PRIMARY 
( NAME = N'oee_db', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.MSSQLSERVER\MSSQL\DATA\oee_db.mdf' , SIZE = 73728KB , MAXSIZE = UNLIMITED, FILEGROWTH = 65536KB )
 LOG ON 
( NAME = N'oee_db_log', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.MSSQLSERVER\MSSQL\DATA\oee_db_log.ldf' , SIZE = 73728KB , MAXSIZE = 2048GB , FILEGROWTH = 65536KB )
 WITH CATALOG_COLLATION = DATABASE_DEFAULT, LEDGER = OFF
GO
ALTER DATABASE [oee_db] SET COMPATIBILITY_LEVEL = 160
GO
IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [oee_db].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO
ALTER DATABASE [oee_db] SET ANSI_NULL_DEFAULT OFF 
GO
ALTER DATABASE [oee_db] SET ANSI_NULLS OFF 
GO
ALTER DATABASE [oee_db] SET ANSI_PADDING OFF 
GO
ALTER DATABASE [oee_db] SET ANSI_WARNINGS OFF 
GO
ALTER DATABASE [oee_db] SET ARITHABORT OFF 
GO
ALTER DATABASE [oee_db] SET AUTO_CLOSE OFF 
GO
ALTER DATABASE [oee_db] SET AUTO_SHRINK OFF 
GO
ALTER DATABASE [oee_db] SET AUTO_UPDATE_STATISTICS ON 
GO
ALTER DATABASE [oee_db] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO
ALTER DATABASE [oee_db] SET CURSOR_DEFAULT  GLOBAL 
GO
ALTER DATABASE [oee_db] SET CONCAT_NULL_YIELDS_NULL OFF 
GO
ALTER DATABASE [oee_db] SET NUMERIC_ROUNDABORT OFF 
GO
ALTER DATABASE [oee_db] SET QUOTED_IDENTIFIER OFF 
GO
ALTER DATABASE [oee_db] SET RECURSIVE_TRIGGERS OFF 
GO
ALTER DATABASE [oee_db] SET  DISABLE_BROKER 
GO
ALTER DATABASE [oee_db] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO
ALTER DATABASE [oee_db] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO
ALTER DATABASE [oee_db] SET TRUSTWORTHY OFF 
GO
ALTER DATABASE [oee_db] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO
ALTER DATABASE [oee_db] SET PARAMETERIZATION SIMPLE 
GO
ALTER DATABASE [oee_db] SET READ_COMMITTED_SNAPSHOT OFF 
GO
ALTER DATABASE [oee_db] SET HONOR_BROKER_PRIORITY OFF 
GO
ALTER DATABASE [oee_db] SET RECOVERY FULL 
GO
ALTER DATABASE [oee_db] SET  MULTI_USER 
GO
ALTER DATABASE [oee_db] SET PAGE_VERIFY CHECKSUM  
GO
ALTER DATABASE [oee_db] SET DB_CHAINING OFF 
GO
ALTER DATABASE [oee_db] SET FILESTREAM( NON_TRANSACTED_ACCESS = OFF ) 
GO
ALTER DATABASE [oee_db] SET TARGET_RECOVERY_TIME = 60 SECONDS 
GO
ALTER DATABASE [oee_db] SET DELAYED_DURABILITY = DISABLED 
GO
ALTER DATABASE [oee_db] SET ACCELERATED_DATABASE_RECOVERY = OFF  
GO
EXEC sys.sp_db_vardecimal_storage_format N'oee_db', N'ON'
GO
ALTER DATABASE [oee_db] SET QUERY_STORE = ON
GO
ALTER DATABASE [oee_db] SET QUERY_STORE (OPERATION_MODE = READ_WRITE, CLEANUP_POLICY = (STALE_QUERY_THRESHOLD_DAYS = 30), DATA_FLUSH_INTERVAL_SECONDS = 900, INTERVAL_LENGTH_MINUTES = 60, MAX_STORAGE_SIZE_MB = 1000, QUERY_CAPTURE_MODE = AUTO, SIZE_BASED_CLEANUP_MODE = AUTO, MAX_PLANS_PER_QUERY = 200, WAIT_STATS_CAPTURE_MODE = ON)
GO
USE [oee_db]
GO
/****** Object:  User [verymaron01]    Script Date: 21/06/2025 14:29:12 ******/
CREATE USER [verymaron01] FOR LOGIN [verymaron01] WITH DEFAULT_SCHEMA=[dbo]
GO
ALTER ROLE [db_owner] ADD MEMBER [verymaron01]
GO
ALTER ROLE [db_datareader] ADD MEMBER [verymaron01]
GO
ALTER ROLE [db_datawriter] ADD MEMBER [verymaron01]
GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_PARTS]    Script Date: 21/06/2025 14:29:12 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_PARTS](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_date] [date] NOT NULL,
	[log_time] [time](7) NOT NULL,
	[line] [varchar](50) NOT NULL,
	[model] [varchar](50) NOT NULL,
	[part_no] [varchar](50) NOT NULL,
	[count_value] [int] NOT NULL,
	[count_type] [varchar](50) NOT NULL,
	[note] [varchar](max) NULL,
	[lot_no] [nvarchar](100) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  View [dbo].[vw_LatestPartCounts]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- VIEW: Latest Part Counts per Line/Model
CREATE VIEW [dbo].[vw_LatestPartCounts] AS
SELECT
    line,
    model,
    part_no,
    MAX(log_date) AS last_date,
    SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
    SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG
FROM dbo.IOT_TOOLBOX_PARTS
GROUP BY line, model, part_no;
GO
/****** Object:  View [dbo].[vw_DailyPartSummary]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_DailyPartSummary]
AS
SELECT
    log_date,
    model,
    part_no,
    count_type,
    SUM(count_value) AS total_count
FROM dbo.IOT_TOOLBOX_PARTS
GROUP BY log_date, model, part_no, count_type;

GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_STOP_CAUSES]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_STOP_CAUSES](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_date] [date] NOT NULL,
	[stop_begin] [datetime] NOT NULL,
	[stop_end] [datetime] NOT NULL,
	[line] [nvarchar](50) NOT NULL,
	[machine] [nvarchar](50) NOT NULL,
	[cause] [nvarchar](255) NOT NULL,
	[note] [nvarchar](255) NOT NULL,
	[recovered_by] [nvarchar](100) NOT NULL,
	[duration]  AS (datediff(minute,[stop_begin],[stop_end])) PERSISTED,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  View [dbo].[vw_StopByMachine]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_StopByMachine]
AS
SELECT
    line,
    machine,
    COUNT(*) AS stop_count,
    SUM(duration) AS total_minutes
FROM dbo.IOT_TOOLBOX_STOP_CAUSES
GROUP BY line, machine;

GO
/****** Object:  View [dbo].[vw_Daily_Part_Summary]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_Daily_Part_Summary] AS
SELECT 
    log_date,
    model,
    part_no,
    count_type,
    SUM(count_value) AS total_count
FROM IOT_TOOLBOX_PARTS
GROUP BY log_date, model, part_no, count_type;

GO
/****** Object:  View [dbo].[vw_Stop_Cause_Summary]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_Stop_Cause_Summary] AS
SELECT 
    log_date,
    line,
    machine,
    cause,
    SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS total_minutes
FROM IOT_TOOLBOX_STOP_CAUSES
GROUP BY log_date, line, machine, cause;

GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_PARAMETER]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_PARAMETER](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[line] [varchar](50) NOT NULL,
	[model] [varchar](100) NOT NULL,
	[part_no] [varchar](100) NOT NULL,
	[planned_output] [int] NOT NULL,
	[updated_at] [datetime] NULL,
	[sap_no] [varchar](100) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [uc_line_model_part_sap] UNIQUE NONCLUSTERED 
(
	[line] ASC,
	[model] ASC,
	[part_no] ASC,
	[sap_no] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_USER_LOGS]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_USER_LOGS](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[action_by] [varchar](100) NULL,
	[action_type] [varchar](20) NULL,
	[target_user] [varchar](100) NULL,
	[detail] [nvarchar](255) NULL,
	[created_at] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[IOT_TOOLBOX_USERS]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[IOT_TOOLBOX_USERS](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[username] [varchar](100) NOT NULL,
	[password] [nvarchar](255) NOT NULL,
	[role] [varchar](50) NULL,
	[created_at] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[username] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Object:  Index [idx_parts_part_no]    Script Date: 21/06/2025 14:29:13 ******/
CREATE NONCLUSTERED INDEX [idx_parts_part_no] ON [dbo].[IOT_TOOLBOX_PARTS]
(
	[part_no] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Object:  Index [idx_stop_date]    Script Date: 21/06/2025 14:29:13 ******/
CREATE NONCLUSTERED INDEX [idx_stop_date] ON [dbo].[IOT_TOOLBOX_STOP_CAUSES]
(
	[log_date] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_PARAMETER] ADD  DEFAULT (getdate()) FOR [updated_at]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_PARTS] ADD  CONSTRAINT [DF_parts_note]  DEFAULT ('-') FOR [note]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_USER_LOGS] ADD  DEFAULT (getdate()) FOR [created_at]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_USERS] ADD  DEFAULT ('user') FOR [role]
GO
ALTER TABLE [dbo].[IOT_TOOLBOX_USERS] ADD  DEFAULT (getdate()) FOR [created_at]
GO
/****** Object:  StoredProcedure [dbo].[sp_AddUser]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[sp_AddUser]
    @username VARCHAR(100),
    @password NVARCHAR(255),
    @role VARCHAR(50) = 'user'
AS
BEGIN
    INSERT INTO IOT_TOOLBOX_USERS (username, password, role, created_at)
    VALUES (@username, @password, @role, GETDATE());
END

GO
/****** Object:  StoredProcedure [dbo].[sp_UpdatePlannedOutput]    Script Date: 21/06/2025 14:29:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[sp_UpdatePlannedOutput]
    @id INT,
    @planned_output INT
AS
BEGIN
    UPDATE IOT_TOOLBOX_PARAMETER
    SET planned_output = @planned_output,
        updated_at = GETDATE()
    WHERE id = @id;
END

GO
USE [master]
GO
ALTER DATABASE [oee_db] SET  READ_WRITE 
GO
